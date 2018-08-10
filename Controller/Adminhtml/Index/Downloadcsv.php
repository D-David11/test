<?php
namespace Emipro\Importexportproductreviews\Controller\Adminhtml\Index;

use Magento\Framework\App\ResourceConnection;
use \Magento\Backend\App\Action\Context;

class Downloadcsv extends \Magento\Backend\App\Action
{
    private $resourceConnection;
    private $fileFactory;
    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->resourceConnection = $resourceConnection;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('rating');
        $sql = "Select rating_code FROM " . $tableName . " ORDER BY rating_id";
        $arrayRate = $connection->fetchAll($sql);
        $outputFile = "sample_data_review_" . date('d-m-Y') . ".csv";
        $ratingRaw1 = "";
        $ratingRaw2 = "";
        $ratingRaw3 = "";
        $csvdata = "";
        $csvdata .= "created_at,sku,status,title,detail,nickname,customer_email";
        foreach ($arrayRate as $value) {
            if ($value["rating_code"] == "Rating") {
                $csvdata .= "," . __('overall_rating');
            } else {
                $csvdata .= "," . __(strtolower($value['rating_code']) . '_rating');
            }
            $ratingRaw1 .= "," . rand(1, 5);
            $ratingRaw2 .= "," . rand(1, 5);
            $ratingRaw3 .= "," . rand(1, 5);
        }
        $csvdata .= "," . __('store_code');
        $csvdata .= "\n";
        $csvdata .= "2017-5-25 10:5:15,sku1,Approved,Satisfied Customer,";
        $csvdata .= "I must say I am very impressed with the quality,Ron,guest";
        $csvdata .= $ratingRaw1 . ",default";
        $csvdata .= "\n";

        $csvdata .= "2017-12-22 1:10:12,sku2,Pending,Great product,";
        $csvdata .=
            "Liked the quality of the shade material. Great bargain for the price.,";
        $csvdata .= "Roni,roni_cost@example.com";
        $csvdata .= $ratingRaw2 . ",default";
        $csvdata .= "\n";

        $csvdata .= "2017-08-28 7:8:12,sku3,Not Approved,poor service,";
        $csvdata .= "I am very please with the look of the blind and the ease of installation.,";
        $csvdata .= "Ktty,guest";
        $csvdata .= $ratingRaw3 . ",default | storename";

        $this->downloadCsv($outputFile, $csvdata);
    }

    private function downloadCsv($file, $csvdata)
    {
        $this->fileFactory->create($file, $csvdata, 'var');
    }
}
