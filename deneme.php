<?php

$mysqli = new mysqli('localhost:3306', 'deneme', '12131213', 'of');

define('USTLIMIT',1.2);
define('ALTLIMIT',1);






if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}


function odenenler($mysqli)
{
    $sql= "SELECT * FROM tester ";
    $odenenler_data=array();
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {

            $odenenler_data[$row['rezno']]['rezervasyon_no'] =  $row['rezno'];
            $odenenler_data[$row['rezno']]['tutar'] =  floatval($row['tutar']);
        }
    }


    return $odenenler_data;
}
function getcc($reznoarray,$conn)
{
    $ccinfo = array();

    $reznocount=count($reznoarray);
    $params=str_repeat("?,",$reznocount);
    $params=rtrim($params,",");
    $types=str_repeat("s",$reznocount);


    $sql = "SELECT rezervasyon_no,tutar,odeme FROM poslar WHERE rezervasyon_no IN ($params) ";
    $stmt=$conn->prepare($sql);


    $stmt->bind_param($types, ...$reznoarray);
    $stmt->execute();

    $result=$stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            @$ccinfo[$row['rezervasyon_no']]['tutar'] +=  floatval($row['tutar']);
            @$ccinfo[$row['rezervasyon_no']]['odeme'] .= $row['odeme'];
        }
    }
    return $ccinfo;
}



function preparestring($data)
{
    $string="";
    $idarray = array();
    foreach ($data as $id){



        $idarray[] = $id['rezervasyon_no'];  ;
    }


    return    $idarray;
}

function gethavale($reznoarray, $conn)
{
    $havaleinfo = array();

    foreach ($reznoarray as $rezno) {
        $sql = "SELECT tutar, odendi FROM kasa WHERE aciklama LIKE ? AND islem_tipi = 'G'";

        $stmt = $conn->prepare($sql);

        $param = '%' . $rezno . '%';
        $stmt->bind_param("s", $param);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                @$havaleinfo[$rezno]['tutar'] += floatval($row['tutar']);
                @$havaleinfo[$rezno]['odeme'] .= $row['odendi'];
            }
        } else {
            $havaleinfo[$rezno][0] = 0;
            $havaleinfo[$rezno][1] = "b";
        }

        // Close the statement
        $stmt->close();
    }

    return $havaleinfo;
}

function mergedata($ccdata,$havaledata,$idarray,$odemedata)
{
    $finaldata = array();

    foreach ($idarray as $id) {
        $finaldata[$id] = array(array());

        $finaldata[$id]['rezno'] = $id;
        @$finaldata[$id]['tutar'] = $ccdata[$id]['tutar'];
        @$finaldata[$id]['durum'] = $ccdata[$id]['odeme'];

        @$finaldata[$id]['tutar'] += $havaledata[$id]['tutar'];
        @$finaldata[$id]['durum'] .= $havaledata[$id]['odeme'];

        $finaldata[$id]['odenen'] = $odemedata[$id]['tutar'];
    }
    return $finaldata;
}

function odemedegerlendir($odenen,$tahsilat)
{
    return $tahsilat<=(USTLIMIT*$odenen) && $tahsilat>=(ALTLIMIT*$odenen);
}
function printres($id,$odenen,$tahsilat,$odendi,$err)
{
        echo "<tr>
    <td>$id</td>
    <td>$odenen</td>
    <td>$tahsilat</td>
    <td>$odendi</td>
    <td>$err</td>
      </tr><br>\n";
}
function odemekontrol($durumlar)
{
    if (strpos($durumlar,"e")!==false){
        return true;
    }
    return false;
}
function process($data)
{
    foreach ($data as $odeme){
        $errmsg = "";
        if (!odemedegerlendir($odeme['odenen'],$odeme['tutar'])){
            $errmsg .= "Tahsilat sorunu";
        }
        if (odemekontrol($odeme['durum'])){
            $errmsg .= "Zaten ödeme yapılmış";
        }
        if ($errmsg==""){
            $errmsg="Sorun yok";
            printres($odeme['rezno'],$odeme['tutar'],$odeme['odenen'],$odeme['durum'],$errmsg);

        }else{
            printres($odeme['rezno'],$odeme['tutar'],$odeme['odenen'],$odeme['durum'],$errmsg);

        }
    }
}

$odenenler_data=odenenler($mysqli);


$idstring=preparestring($odenenler_data);

$ccdata=getcc($idstring,$mysqli);
$havaledata=gethavale($idstring,$mysqli);
$mysqli->close();
$data = mergedata($ccdata,$havaledata,$idstring,$odenenler_data);

process($data);
