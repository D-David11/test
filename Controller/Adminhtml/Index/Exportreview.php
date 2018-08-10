<?php
namespace Emipro\Importexportproductreviews\Controller\Adminhtml\Index;

use \Magento\Backend\App\Action\Context;
use \Magento\Customer\Model\CustomerFactory;
use \Magento\Framework\App\ResourceConnection;
use \Magento\Store\Model\StoreRepository;

class Exportreview extends \Magento\Backend\App\Action
{
    private $productRepository;
    private $reviewCollection;
    private $resourceConnection;
    private $ratingFactory;
    private $storeManager;
    private $customer;
    private $ratingsVote;
    private $downloader;
    private $directory;
    private $storeRepository;
    private $customerFactory;
    private $storeCode = [];
    private $io;

    public function __construct(
        Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Review\Model\ResourceModel\Review\CollectionFactory $reviewCollection,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Customer $customers,
        \Magento\Review\Model\RatingFactory $ratingFactory,
        \Magento\Framework\Filesystem\DirectoryList $directory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\Filesystem\Io\File $io,
        CustomerFactory $customerFactory,
        ResourceConnection $resourceConnection,
        StoreRepository $storeRepository
    ) {
        parent::__construct($context);
        $this->directory = $directory;
        $this->_file = $file;
        $this->io = $io;
        $this->resourceConnection = $resourceConnection;
        $this->downloader = $fileFactory;
        $this->productRepository = $productRepository;
        $this->reviewCollection = $reviewCollection;
        $this->customer = $customers;
        $this->ratingFactory = $ratingFactory;
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
    }

    public function execute()
    {
        $toDate = $this->getRequest()->getParam('to_date');
        $fromDate = $this->getRequest()->getParam('from_date');
        $collection = $this->reviewCollection->create();
        if ($toDate != "") {
            $newTodate = date('Y-m-d H:i:s', strtotime($toDate));
            $collection->addFieldToFilter(
                'created_at',
                ['lteq' => $newTodate]
            );
        }
        if ($fromDate != "") {
            $newFromdate = date('Y-m-d H:i:s', strtotime($fromDate));
            $collection->addFieldToFilter(
                'created_at',
                ['gteq' => $newFromdate]
            );
        }
        $collection->setDateOrder();
        $countCollection = count($collection);
        if ($countCollection > 0) {
            $stores = $this->storeRepository->getList();
            foreach ($stores as $store) {
                $sID = $store["store_id"];
                $this->storeCode[$store["store_id"]] = $store["code"];
            }
            $tmpBasePath = "pub/media/importexportproductreviews/tmp/file/";
            $tmpBaseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
            $base_url = $tmpBaseUrl . $tmpBasePath;
            $collectionCount = count($collection);
            $heading = [
                __('created_at'),
                __('sku'),
                __('status'),
                __('title'),
                __('detail'),
                __('nickname'),
                __('email'),
            ];
            $arrayRate = $this->getRatingCode();
            foreach ($arrayRate as $value) {
                if ($value["rating_code"] == "Rating") {
                    array_push($heading, __('overall_rating'));
                } else {
                    array_push($heading, __(strtolower($value['rating_code']) . '_rating'));
                }
            }
            array_push($heading, __('store_code'));
            $outputFile = "export_review_" . date('d-m-Y') . ".csv";
            $tmpPath = "/pub/media/importexportproductreviews/tmp/file/";
            if (!$this->_file->isExists($this->directory->getRoot() . $tmpPath)) {
                $this->io->mkdir($this->directory->getRoot() . $tmpPath, 0777, true);
            }
            $storeCsv = $this->directory->getRoot() . "/pub/media/importexportproductreviews/tmp/file/";
            $handle = fopen($storeCsv . $outputFile, 'w');
            fputcsv($handle, $heading);
            $tmpcnt = 1;
            foreach ($collection as $item) {
                $row1 = $this->createCsv($item, $handle);
                fputcsv($handle, $row1);
            }
            $file = $this->directory->getPath("media") . "/importexportproductreviews/tmp/file/" . $outputFile;
            $rootDir = $this->directory->getRoot() . $file;
            $resultRedirect = $this->resultRedirectFactory->create();
            if ($collectionCount > 0) {
                $this->messageManager->addSuccess(__("$collectionCount Reviews Exported Successfully...!!!"));
                $tmpDownload = "File Is Ready For Download...<a href='$base_url$outputFile'>$outputFile</a>";
                $this->messageManager->addSuccess(__($tmpDownload));
            }
            return $resultRedirect->setPath('importexportproductreviews/index/index');
        } else {
            $msg = "No Reviews Found For Export ";
            if ($fromDate != null) {
                $msg .= "From " . $fromDate;
            }
            if ($toDate != null) {
                $msg .= " To " . $toDate;
            }
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addNotice(__($msg . "...!!!"));
            return $resultRedirect->setPath('importexportproductreviews/index/index');
        }
    }

    private function getRatingCode()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('rating');
        $sql = "Select rating_id,rating_code FROM " . $tableName . " ORDER BY rating_id";
        $arrayRate = $connection->fetchAll($sql);

        return $arrayRate;
    }

    private function createCsv($item)
    {
        $item->getData('review_id');
        $row1 = [];
        array_push(
            $row1,
            $item->getData('created_at'),
            $this->getProductSku($item->getData('entity_pk_value')),
            $this->getReviewStatus($item->getData('status_id')),
            $item->getData('title'),
            $item->getData('detail'),
            $item->getData('nickname')
        );
        if ($item->getData('customer_id') > 0) {
            array_push($row1, $this->getCustomerEmail($item->getData('customer_id')));
        } else {
            array_push($row1, "guest");
        }
        $this->getRatingsInfo($item->getData('review_id'));
        $arrayRatingCode = $this->getRatingCode();
        try {
            foreach ($arrayRatingCode as $value) {
                if (empty($this->ratingsVote)) {
                    array_push($row1, "");
                } else {
                    if (array_key_exists($value['rating_id'], $this->ratingsVote)) {
                        array_push($row1, $this->ratingsVote[$value['rating_id']]);
                    } else {
                        array_push($row1, "");
                    }
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $tmp1 = null;
        }
        $this->ratingsVote = null;
        array_push($row1, $this->getAllStoreId($item->getData('review_id')));
        return $row1;
    }

    private function getProductSku($productId)
    {
        $product = $this->productRepository->getById($productId);
        return $product->getData("sku");
    }

    private function getReviewStatus($statusCode)
    {
        if ($statusCode == 1) {
            return "Approved";
        } elseif ($statusCode == 2) {
            return "Pending";
        } elseif ($statusCode == 3) {
            return "Not Approved";
        }
    }

    private function getCustomerEmail($customerId)
    {
        $customer = $this->customerFactory->create();
        $customer->load($customerId);
        return $customer->getData("email");
    }

    private function getRatingsInfo($reviewId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getConnection();
        $tableName = $connection->getTableName('rating_option_vote');
        $sql = "Select * FROM " . $tableName . " where review_id=" . $reviewId;
        $arrayRate = $connection->fetchAll($sql);
        foreach ($arrayRate as $value) {
            $this->ratingsVote[$value['rating_id']] = $value['value'];
        }
    }

    private function getAllStoreId($reviewId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getConnection();
        $tableName = $connection->getTableName('review_store');
        $sql = "Select * FROM " . $tableName . " where review_id=" . $reviewId;
        $arrayRate = $connection->fetchAll($sql);
        $countArrayrate = count($arrayRate);
        if ($countArrayrate > 0) {
            $tmpStoreId = "";
            foreach ($arrayRate as $value) {
                if (array_key_exists($value['store_id'], $this->storeCode) && $value['store_id'] != 0) {
                    $tmpStoreId .= $this->storeCode[$value['store_id']] . " | ";
                }
            }
            $tmpStoreId = rtrim($tmpStoreId, " | ");
            return $tmpStoreId;
        }
    }
}
