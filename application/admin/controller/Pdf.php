<?php
/**
 * +----------------------------------------------------------------------
 * | 广告管理控制器
 * +----------------------------------------------------------------------
 */
namespace app\admin\controller;
use think\Controller;
use think\Loader;
use think\Db;

use think\facade\Env;

class Pdf extends Controller
{
    
    
    function index(){
        
        $riqi = date('Ymd',time());
    //     $where['time'] = $riqi;
    //     $info = Db::name('daxuetang')->where($where)->select();
        
    //     if(count($info) == 0){
    //         $rs_arr['status'] = 500;
    // 		$rs_arr['msg'] = '仅限大学堂当日打印';
    // 		return json_encode($rs_arr,true);
    // 		exit;
    //     }
        
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $text = md5($riqi.'core2022');
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Nicola Asuni');
$pdf->SetTitle('TCPDF Example 061');
$pdf->SetSubject('TCPDF Tutorial');
$pdf->SetKeywords('TCPDF, PDF');


// set header and footer fonts
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font
$pdf->SetFont('stsongstdlight', '', 14);

// add a page
$pdf->AddPage();

/* NOTE:
 * *********************************************************
 * You can load external XHTML using :
 *
 * $html = file_get_contents('/path/to/your/file.html');
 *
 * External CSS files will be automatically loaded.
 * Sometimes you need to fix the path of the external CSS.
 * *********************************************************
 */

// define some HTML content with style
$html = <<<EOF
<!-- EXAMPLE OF CSS STYLE -->
<style>
    .tit_bt{
        font-size: 22px;
        color: #333333;
        font-weight: bold;
        text-align:center;
    }
    .tit_one{
        font-size: 16px;
        color: #333333;
        font-weight: bold;
    }
    .tit_two{
        font-size: 18px;
        color: #333333;
        font-weight: bold;
    }
    .border{
        line-height: 6px;
        border-bottom: 1px dashed #f4f4f4;
    }
    table tr{
        
    }
    table td{
        line-height: 29px;
        border-bottom: 1px dashed #f4f4f4;
    }
</style>
<span class="tit_bt">CU文化规章@21 空白版</span><br /><br />
<span class="tit_two">周周篇</span><br />
<span class="tit_two">经营方针</span><br />
<div class="border"></div>
<div class="border"></div>
<div class="border"></div>
<div class="border"></div><br />
<span class="tit_two">两学一做</span><br />
<div class="border"></div>
<div class="border"></div>
<div class="border"></div><br />
<span class="tit_two">成长法则</span><br />
<div class="border"></div>
<div class="border"></div>
<div class="border"></div>
<div class="border"></div>
<div class="border"></div><br />
<span class="tit_two">库尔精神</span><br />
<table>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
</table>
EOF;

// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

$style = array(
            'border' => 0,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );
       // QRCODE,Q : QR-CODE Better error correction
       //二维码1
       
       $pdf->write2DBarcode($text, 'QRCODE,L', 160, 236, 60, 60, $style, 'N');
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

$pdf->AddPage();
$html = <<<EOF
<!-- EXAMPLE OF CSS STYLE -->
<style>
    .tit_one{
        font-size: 16px;
        color: #333333;
        font-weight: bold;
    }
    .tit_two{
        font-size: 18px;
        color: #333333;
        font-weight: bold;
    }
    .border{
        line-height: 8px;
        border-bottom: 1px dashed #f4f4f4;
    }
    table tr{
        
    }
    table td{
        line-height: 31px;
        border-bottom: 1px dashed #f4f4f4;
    }
</style>
<span class="tit_two">办事原则</span><br />
<div class="border"></div>
<div class="border"></div><br />
<span class="tit_two">议事原则</span><br />
<div class="border"></div>
<div class="border"></div>
<div class="border"></div><br />
<span class="tit_two">生产准则</span><br />
<div class="border"></div>
<div class="border"></div><br />
<span class="tit_two">做人准则</span><br />
<div class="border"></div><br />
<span class="tit_two">CU信念</span><br />
<div class="border"></div><br />
<span class="tit_two">CU宗旨</span><br />
<div class="border"></div><br />
<span class="tit_two">CU口号</span><br />
<div class="border"></div><br />
<span class="tit_two">CU愿景</span><br />
<table>
    <tr>
        <td width="80%"></td>
    </tr>
</table><br />

<div class="tit_two">CU人才观</div>
<table>
    <tr>
        <td width="80%"></td>
    </tr>
    <tr>
        <td width="80%"></td>
    </tr>
</table>
EOF;

// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

$style = array(
            'border' => 0,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );
       // QRCODE,Q : QR-CODE Better error correction
       //二维码1
        $pdf->write2DBarcode($text, 'QRCODE,L', 160, 236, 60, 60, $style, 'N');
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// add a page
$pdf->AddPage();
$html = <<<EOF
<!-- EXAMPLE OF CSS STYLE -->
<style>
    .tit_one{
        font-size: 16px;
        color: #333333;
        font-weight: bold;
    }
    .tit_two{
        font-size: 18px;
        color: #333333;
        font-weight: bold;
    }
    .border{
        line-height: 9px;
        border-bottom: 1px dashed #f4f4f4;
    }
    table tr{
        
    }
    table td{
        line-height: 31px;
        border-bottom: 1px dashed #f4f4f4;
    }
</style>
<span class="tit_two">CU常态化</span><br />
<div class="border"></div>
<div class="border"></div>
<div class="border"></div><br />
<span class="tit_two">CU不二胡</span><br />
<div class="border"></div>
<div class="border"></div>
<div class="border"></div>
<div class="border"></div>
<div class="border"></div>
<div class="border"></div>
<div class="border"></div>
<div class="border"></div><br />

EOF;

// output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

$style = array(
            'border' => 0,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );
       // QRCODE,Q : QR-CODE Better error correction
       //二维码1
       $pdf->write2DBarcode($text, 'QRCODE,L', 160, 236, 60, 60, $style, 'N');
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


// reset pointer to the last page
$pdf->lastPage();

   
//如果要对html指定 宽度  writeHTMLCell更方便
//$pdf->writeHTMLCell(100, 100, 100, 50, $html2, 0, 1, 0, true, '', true);
        //如果要对html指定 宽度  writeHTMLCell更方便
        //$pdf->writeHTMLCell(0, 0, 0, 0, $html, 0, 0, 0, true, '', true);
        //直接输入到浏览器
        //PDF输出   I：在浏览器中打开，D：下载，F：在服务器生成pdf ，S：只返回pdf的字符串
        $pdf->Output('demo.pdf', 'I');
    }
    
    
    
    //备份
    function index——bf(){
        
        //新建一个PDF文档
        //L 横排   P竖排
        $url = 'http://coretests.com/';
        $orientation='P';
        $unit='mm';
        $format='A4';
        $unicode=true;
        $encoding='UTF-8';
        $diskcache=false;
        $pdfa=false;
        $logo = '/uploads/qrcode/1594350351.png';
        $pdf = new \TCPDF($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        
        
        //设置默认的等宽字体
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        //定义左、上、右页边距。
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        //设置自动分页符
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        //设置图片比例
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        //添加一个页面
        $pdf->AddPage();
    
        $pdf->SetFont('stsongstdlight', '', 14); // 使用中文字体，不设置这个，中文就会乱码
        // 要写入的html内容
        $html = '<h1 align="center">CU 文化规章@21 空白版</h1><br><h3>时刻篇<br>TKR<br><br><br></h2><h3>周周篇<br>经营方针<br><br><br><br><br>两学一做<br><br><br><br><br>成长法则<br><br><br><br><br><br><br><br>库尔精神<br></h3>';
        //输出html内容
        
        $pdf->writeHTML($html, true, 0, true, 0);
        //重置指向页码，指向最后一页
        $pdf->lastPage();
        
        $style = array(
            'border' => 0,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(255,255,255),
            'bgcolor' => true, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );
       // QRCODE,Q : QR-CODE Better error correction
       //二维码1
        $pdf->write2DBarcode($url, 'QRCODE,a', 170, 246, 30, 30, $style, 'N');
        
        $html = '<h3>办事原则<br><br><br><br><br>议事原则<br><br><br><br><br>生产准则<br><br><br><br>做人准则<br><br><br>五个意识<br><br><br>CU信念<br><br><br>CU宗旨<br><br><br>CU口号<br><br><br>CU愿景<br><br><br>CU人才观</h3>';
        //输出html内容
        $pdf->writeHTML($html, true, 0, true, 0);
        
        //二维码2
        $pdf->write2DBarcode($url, 'QRCODE,a', 170, 246, 30, 30, $style, 'N');
        
        $html = '<h3>CU常态化<br><br><br><br><br><br>CU不二胡<br><br><br><br><br><br><br><br><br><br><br><br><br>CU宣誓词<br><br><br><br><br>大自然科学原理<br><br><br><br><br></h3>';
        //输出html内容
        $pdf->writeHTML($html, true, 0, true, 0);
        
        //二维码3
        $pdf->write2DBarcode($url, 'QRCODE,a', 170, 246, 30, 30, $style, 'N');
        
        //如果要对html指定 宽度  writeHTMLCell更方便
        //$pdf->writeHTMLCell(100, 100, 100, 50, $html2, 0, 1, 0, true, '', true);
        //如果要对html指定 宽度  writeHTMLCell更方便
        //$pdf->writeHTMLCell(0, 0, 0, 0, $html, 0, 0, 0, true, '', true);
        //直接输入到浏览器
        //PDF输出   I：在浏览器中打开，D：下载，F：在服务器生成pdf ，S：只返回pdf的字符串
        $pdf->Output('demo.pdf', 'I');
    }
    
}
