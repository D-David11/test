<?php
namespace Emipro\Importexportproductreviews\Controller\Adminhtml\Index;

use Magento\Framework\App\ResourceConnection;
use \Magento\Backend\App\Action\Context;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Customer\Api\CustomerRepositoryInterface;
use \Magento\Customer\Model\CustomerFactory;
use \Magento\Framework\Filesystem\DirectoryList;
use \Magento\Framework\Filesystem\Driver\File;
use \Magento\MediaStorage\Model\File\UploaderFactory;
use \Magento\Review\Model\RatingFactory;
use \Magento\Review\Model\ReviewFactory;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Store\Model\StoreRepository;

class Import extends \Magento\Backend\App\Action
{

    private $uploaderFactory;
    private $resourceConnection;
    private $directoryList;
    private $basepath;
    private $productRepository;
    private $listSKU = [];
    private $skuFlag = 0;
    private $listBlank = [];
    private $listBlankFlag = 0;
    private $insertedRows = 0;
    private $customerRepositoryInterface;
    private $storeRepository;
    private $storeCode;
    private $invalidCode;
    private $invalidEmail;
    private $invalidTimestamp;
    private $invalidRating;
    private $customerFactory;
    private $invalidStatus;
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepositoryInterface,
        DirectoryList $directoryList,
        File $file,
        UploaderFactory $uploaderFactory,
        RatingFactory $ratingFactory,
        ReviewFactory $reviewFactory,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        ResourceConnection $resourceConnection,
        StoreRepository $storeRepository
    ) {
        parent::__construct($context);
        $this->customerFactory = $customerFactory;
        $this->resourceConnection = $resourceConnection;
        $this->productRepository = $productRepository;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->directoryList = $directoryList;
        $this->uploaderFactory = $uploaderFactory;
        $this->_file = $file;
        $this->_ratingFactory = $ratingFactory;
        $this->_reviewFactory = $reviewFactory;
        $this->_storeManager = $storeManager;
        $this->basepath = $this->getBaseDir();
        $this->storeRepository = $storeRepository;
        $this->storeCode = [];
    }

    public function execute()
    {
        if ($this->getRequest()->getParam('csv_import')) {
            $csvfile = $this->getRequest()->getParam('csv_import');
            $fileName = $csvfile[0]['name'];
            $this->setAllStoreCodes();
            $resultRedirect = $this->resultRedirectFactory->create();
            try {
                if ($this->getRequest()->getParam('csv_import')) {
                    $this->readCsvfile($fileName);
                    if ($this->skuFlag == 1) {
                        $concatString = "";
                        $concatString .= "<p style='word-wrap: break-word;'>Product's Not Available For ";
                        foreach ($this->listSKU as $value) {
                            $concatString .= $value . ",";
                        }
                        $concatString = rtrim($concatString, ",");
                        $concatString .= "  sku | Insert Valid Product SKU In CSV File.</p>";
                        $this->messageManager->addError(__($concatString));
                    }
                    if ($this->insertedRows > 0) {
                        $this->messageManager->addSuccess(__("$this->insertedRows Review Imported Successfully."));
                    }
                    if ($this->listBlankFlag == 1) {
                        $concatString = "";
                        $concatString .= "<p style='word-wrap: break-word;'>Blank Values Found In Line Number ";
                        foreach ($this->listBlank as $value) {
                            $concatString .= $value . ",";
                        }
                        $concatString = rtrim($concatString, ",");
                        $concatString .= "  | Blank Values Is Not Allowed In CSV File.</p>";
                        $this->messageManager->addError(__($concatString));
                    }
                    if ($this->invalidEmail != "") {
                        $tmpinvalidEmail = "<p style='word-wrap: break-word;'>";
                        $tmpinvalidEmail .= rtrim($this->invalidEmail, ",");
                        $tmpMsg = " | Email Address Not Associated To Any Customer</p> ";
                        $this->messageManager->addError(__($tmpinvalidEmail . $tmpMsg));
                        $this->messageManager->addError(__("Enter Valid Email Address Or Add As A Guest Review..!!!"));
                    }
                    if ($this->invalidCode != "") {
                        $tmpinvalidCode = rtrim($this->invalidCode, ",");
                        $tmpMsg = "<p style='word-wrap: break-word;'>Invalid Store Code Found In Line Number ";
                        $this->messageManager->addError(__($tmpMsg . $tmpinvalidCode . "</p>"));
                        $tmpMsg = "If You Want Add Multiple Store Code Then Separate It By <b>|</b> (Pipe) Sign... ";
                        $this->messageManager->addError(__($tmpMsg));
                    }
                    if ($this->invalidTimestamp != "") {
                        $tmpP = "<p style='word-wrap: break-word;'>";
                        $tmpTimeZone = "Invalid Time Zone In Line Number ";
                        $invalidTimestamp = rtrim($this->invalidTimestamp, ",");
                        $this->messageManager->addError(__($tmpP . $tmpTimeZone . $invalidTimestamp . "</p>"));
                    }
                    if ($this->invalidStatus != "") {
                        $tmpinvalidStatus = rtrim($this->invalidStatus, ",");
                        $tmpMsgStatus = "<p style='word-wrap: break-word;'>Invalid Status In Line Number ";
                        $tmpMsgStatus1 = " Allowed Only 'Pending' or 'Approved' or 'Not Approved'</p>";
                        $this->messageManager->addError(__($tmpMsgStatus . $tmpinvalidStatus . $tmpMsgStatus1));
                    }
                    if ($this->invalidRating != "") {
                        $invalidRating = rtrim($this->invalidRating, ",");
                        $tmpMsg = "<p style='word-wrap: break-word;'>Invalid Ratings In Line Number ";
                        $tmpMsg1 = " | Allowd Only Numeric Values Between 1 To 5</p>";
                        $this->messageManager->addError(__($tmpMsg . $invalidRating . $tmpMsg1));
                    }
                    $this->_file->deleteFile($this->basepath . $fileName);
                    $resultRedirect = $this->resultRedirectFactory->create();
                    return $resultRedirect->setPath('importexportproductreviews/index/index');
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError(__("Product Review has not been imported successfully...!!!"));
                return $resultRedirect->setPath('importexportproductreviews/index/index');
            }
        } else {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addNotice(__("Select CSV File To Import.!!!"));
            return $resultRedirect->setPath('importexportproductreviews/index/index');
        }
    }

    /*fetch data from csv file*/
    private function readCsvfile($fileName)
    {
        try {
            $row = 1;
            $value = [];
            if (($handle = fopen($this->basepath . $fileName, "r")) !== false) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $num = $this->countColumn($data);
                    if ($row > 1) {
                        for ($c = 0; $c < $num; $c++) {
                            $value[] = $data[$c];
                        }
                        $this->setReview($value, $row);
                        $value = [];
                    }
                    $row++;
                }
                fclose($handle);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->redirectErrorPage();
        }
    }
    private function countColumn($data)
    {
        return count($data);
    }
    /*set ratings & reviews*/
    private function setReview($reviewAdd, $rowNo)
    {
        try {
            $sku = $this->getProductId($reviewAdd[1]);
            $emailValid = 0;
            $timestampValid = 1;
            $ratingValid = 1;
            if ($reviewAdd[6] != "") {
                if (trim(strtolower($reviewAdd[6])) == "guest") {
                    $emailValid = 1;
                } else {
                    if ($email = $this->getCustomerIdByEmail($reviewAdd[6])) {
                        $emailValid = 1;
                    } else {
                        $this->invalidEmail .= $reviewAdd[6] . ",";
                    }
                }
            }
            if ($date = $this->getTimestamp($reviewAdd[0])) {
                $timestamp = 1;
            } else {
                $this->invalidTimestamp .= $rowNo . ",";
            }
            $tmpStatus = strtolower(trim($reviewAdd[2]));
            $checkStatus = 0;
            if ($tmpStatus == "pending" || $tmpStatus == "approved" || $tmpStatus == "not approved") {
                $checkStatus = 1;
            } else {
                $this->invalidStatus .= $rowNo . ",";
            }
            $ratingsFlag = 0;
            $ratingEntity = $this->getRatingEntity();
            $ratingIndex = 1;
            $ratingIncement = 0;
            for ($i = 7; $i < (7 + $ratingEntity); $i++) {
                if (trim($reviewAdd[$i]) > 0 && trim($reviewAdd[$i]) < 6) {
                    $reviewFinalData['ratings'][$ratingIndex] = floor($ratingIncement + trim($reviewAdd[$i]));
                    $ratingsFlag = 1;
                } elseif (trim($reviewAdd[$i]) != "") {
                    $ratingValid = 0;
                    $this->invalidRating .= $rowNo . ",";
                    break;
                }
                $ratingIndex++;
                $ratingIncement += 5;
            }
            for ($i = 0; $i <= 6; $i++) {
                if (trim($reviewAdd[$i]) == "") {
                    $this->listBlank[] = $rowNo;
                    $this->listBlankFlag = 1;
                }
            }
            if (!$setID = $this->checkStoreCodes($reviewAdd)) {
                $this->invalidCode .= $rowNo . ",";
            } elseif ($sku && $emailValid == 1 && $timestamp == 1 && $ratingValid == 1 && $checkStatus == 1 && $this->listBlankFlag != 1) {
                $productId = $sku;
                $reviewFinalData['nickname'] = $reviewAdd[5];
                $reviewFinalData['title'] = $reviewAdd[3];
                $reviewFinalData['detail'] = $reviewAdd[4];
                $review = $this->_reviewFactory->create()->setData($reviewFinalData);
                $review->setEntityId($review->getEntityIdByCode(\Magento\Review\Model\Review::ENTITY_PRODUCT_CODE));
                $review->setEntityPkValue($productId);
                if (trim(strtolower($reviewAdd[6])) == "guest") {
                    $tmp = null;
                } elseif ($reviewAdd[6] != "") {
                    if ($email = $this->getCustomerIdByEmail($reviewAdd[6])) {
                        $review->setCustomerId($email);
                    }
                }
                if (strtolower(trim($reviewAdd[2])) == "pending") {
                    $review->setStatusId(\Magento\Review\Model\Review::STATUS_PENDING);
                } elseif (strtolower(trim($reviewAdd[2])) == "approved") {
                    $review->setStatusId(\Magento\Review\Model\Review::STATUS_APPROVED);
                } else {
                    $review->setStatusId(\Magento\Review\Model\Review::STATUS_NOT_APPROVED);
                }
                $review->setStoreId($this->_storeManager->getStore()->getId())
                    ->setStores($setID)
                    ->save();
                if ($ratingsFlag == 1) {
                    foreach ($reviewFinalData['ratings'] as $ratingId => $optionId) {
                        $this->_ratingFactory->create()
                            ->setRatingId($ratingId)
                            ->setReviewId($review->getId())
                            ->addOptionVote($optionId, $productId);
                    }
                }
                $review->aggregate();

                $tmpId = $review->getData('review_id');
                $connection = $this->resourceConnection->getConnection();
                $connection->update(
                    'review',
                    [
                        'created_at' => $date,
                    ],
                    ["review_id = ?" => $tmpId]
                );
                $review->unsetData('review_id');
                $this->insertedRows++;
            }
            if ($sku == false) {
                $this->listSKU[] = $reviewAdd[1];
                $this->skuFlag = 1;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->redirectErrorPage();
        }
    }

    /*Fetch Rating Entity*/
    private function getRatingEntity()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('rating');
        $sql = "Select rating_code FROM " . $tableName . " ORDER BY rating_id";
        $arrayRate = $connection->fetchAll($sql);
        return count($arrayRate);
    }

    /*get product id by sku*/
    private function getProductId($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            return $product->getEntityId();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }

    /*get customer id by email*/
    private function getCustomerIdByEmail($email)
    {
        try {
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId(1);
            $customer->loadByEmail($email);
            $customerId = $customer->getData("entity_id");
            if (isset($customerId)) {
                return $customerId;
            } else {
                return false;
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }

    /*set time stamp*/
    private function getTimestamp($timezone)
    {
        if (($timestamp = strtotime($timezone)) === false) {
            return false;
        } else {
            return date('Y-m-d h:i:s', $timestamp);
        }
    }

    private function getBaseDir()
    {
        return $this->directoryList->getRoot() . "/pub/media/importexportproductreviews/tmp/file/";
    }

    /*fetch all store id & save it*/
    private function setAllStoreCodes()
    {
        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            $sID = $store["store_id"];
            $this->storeCode[$store["code"]] = $store["store_id"];
        }
    }

    /*set array for store id*/
    private function checkStoreCodes($columns)
    {
        $columnIndex = 7 + $this->getRatingEntity();
        try {
            if ($columns[$columnIndex] != "") {
                $tmp = [];
                $listCode = explode("|", $columns[$columnIndex]);
                foreach ($listCode as $value) {
                    $tmpValue = strtolower(trim($value));
                    if (array_key_exists($tmpValue, $this->storeCode)) {
                        array_push($tmp, $this->storeCode[$tmpValue]);
                    } else {
                        return false;
                    }
                }
                return array_unique($tmp);
            } else {
                return false;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return false;
        }
    }

    private function redirectErrorPage()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->messageManager->addError(__("Please select valid csv file."));
        return $resultRedirect->setPath('importexportproductreviews/index/index');
    }
}
