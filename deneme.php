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
    $odenenler_data=array(0=>array());
    $result = $mysqli->query($sql);
    if ($result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {

            $odenenler_data[$row['rezno']][0] =  $row['rezno'];
            $odenenler_data[$row['rezno']][1] =  floatval($row['tutar']);
        }
    }

    unset($odenenler_data[0]);

    return $odenenler_data;
}

function getcc($idstring,$conn)
{
    $ccinfo = array();
    
    $sql = "SELECT rezervasyon_no,tutar,odeme FROM poslar WHERE rezervasyon_no IN ($idstring) ";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            @$ccinfo[$row['rezervasyon_no']][0] +=  floatval($row['tutar']);
            @$ccinfo[$row['rezervasyon_no']][1] .= $row['odeme'];
        }
    }
    return $ccinfo;
}
function preparestring($data)
{
    $string="";
    $idarray = array();
    foreach ($data as $id){

        $string .= "'";
        $string .= $id[0];
        $string .= "'";

        $string.=",";

        $idarray[] = $id[0];  ;
    }

    $string=rtrim($string, ",");
    $stringdata = array($string,$idarray);

    return    $stringdata ;
}

function gethavale($reznoarray,$conn)
{
    $havaleinfo = array();

    foreach ($reznoarray as $rezno){
        $sql = "SELECT tutar, odendi FROM kasa WHERE aciklama LIKE '%$id%';";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                @$havaleinfo[$id][0] +=  floatval($row['tutar']);
                @$havaleinfo[$id][1] .= $row['odendi'];
            }
        }else{
            $havaleinfo[$id][0] = 0;
            $havaleinfo[$id][1] = "b";
        }

    }

    return $havaleinfo;
}
function mergedata($ccdata,$havaledata,$idarray,$odemedata)
{
    $finaldata = array();

    foreach ($idarray as $id) {
        $finaldata[$id] = array(0, "", 0, 0);

        $finaldata[$id][3] = $id;
        @$finaldata[$id][0] = $ccdata[$id][0];
        @$finaldata[$id][1] = $ccdata[$id][1];

        $finaldata[$id][0] += $havaledata[$id][0];
        $finaldata[$id][1] .= $havaledata[$id][1];

        $finaldata[$id][2] = $odemedata[$id][1];
    }
    return $finaldata;
}

function odemedegerlendir($odenen,$tahsilat)
{
    global $ustlımıt;
    global $altlımıt;

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
        if (!odemedegerlendir($odeme[2],$odeme[0])){
            $errmsg .= "Tahsilat sorunu";
        }
        if (odemekontrol($odeme[1])){
            $errmsg .= "Zaten ödeme yapılmış";
        }
        if ($errmsg==""){
            $errmsg="Sorun yok";
            printres($odeme[3],$odeme[2],$odeme[0],$odeme[1],$errmsg);

        }else{
            printres($odeme[3],$odeme[2],$odeme[0],$odeme[1],$errmsg);

        }
    }
}

$odenenler_data=odenenler($mysqli);


$idstring=preparestring($odenenler_data);

$ccdata=getcc($idstring[0],$mysqli);
$havaledata=gethavale($idstring[1],$mysqli);
$mysqli->close();
$data = mergedata($ccdata,$havaledata,$idstring[1],$odenenler_data);

process($data);
