<?php
namespace app\api\controller;
use think\Controller;
use think\Db;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export extends Controller
{
        
    public function index(){
        //接收前端参数 查询数据出来 目前演示为测试数据
        $data = [
            [
                "id" => 1,
                "name" => "小黄",
                "age" => "10"
            ],
            [
                "id" => 2,
                'name' => "小红",
                "age" => "11",
            ],
            [
                "id" => 3,
                "name" => "小黑",
                "age" => "12"
            ]
        ];
        
        
        $fileName = '('.date("Y-m-d",time()) .'导出）';
        //实例化spreadsheet对象
        $spreadsheet = new Spreadsheet();
        //获取活动工作簿
        $sheet = $spreadsheet->getActiveSheet();
        //设置单元格表头
        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('B1', '姓名');
        $sheet->setCellValue('C1', '年龄');
      

        $i=2;
        foreach($data as $key => $val){
           
            $sheet->SetCellValueByColumnAndRow('1',$i,$val['id']);
            $sheet->SetCellValueByColumnAndRow('2',$i,$val['name']);
            $sheet->SetCellValueByColumnAndRow('3',$i,$val['age']);
            
            $color = '000000';
            if($val['name'] == '小黄')
               $color = 'CCFF33';
            if($val['name'] == '小红')
               $color = 'B8002E';
            if($val['name'] == '小黑')
               $color = '000000';
            
            $data[$key]['color'] = $color;
            
            $cell = 'B'.$i;
            $spreadsheet->getActiveSheet()->getStyle($cell)->getFont()->getColor()->setRGB($color);
            
            $i++;
        
        }
        
        //MIME协议，文件的类型，不设置描绘默认html
        header('Content-Type:application/vnd.openxmlformats-officedoument.spreadsheetml.sheet');
        //MIME 协议的扩展
        header("Content-Disposition:attachment;filename={$fileName}.xlsx");
        //缓存控制
        header('Cache-Control:max-age=0');
        
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet,'Xlsx');
        $writer->save('php://output');

    }

}