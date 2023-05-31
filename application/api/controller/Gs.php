<?php
namespace app\api\controller;

use TCPDFBarcode;
use TCPDF;

class Gs
{
    public function generateDMCode(){
        $data = "(01)12345678901234"; // 替换为实际的DM码数据
        $outputFile = '/www/wwwroot/cutest/public/uploads/123.jpg'; // 设置生成的DM码的保存路径
    
        $pdf = new TCPDF();
        $pdf->AddPage();
    
        // 绘制Data Matrix码
        $pdf->write2DBarcode($data, 'DATAMATRIX');
    
        $pdf->Output($outputFile, 'F');
    
        echo "DM码已生成并保存在：" . $outputFile;
    }
}