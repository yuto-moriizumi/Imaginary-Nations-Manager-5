<?php
error_reporting(0);
mb_language("ja");
mb_internal_encoding('UTF-8');

//JSから受信
$url=$_POST["data"];
$command = explode(",",$_POST["data"],2); //命令取り出し

if( strcmp($command[0], "selectProvince") == 0 ){ //マップクリック処理
    $operand = explode(",",$command[1]);
    //色を取得
    $image = imagecreatefrompng("map.png");
    $rgb = imagecolorat($image, $operand[0], $operand[1]);
    $colors = imagecolorsforindex($image, $rgb);
    $targetCountryColor = $colors["red"].".".$colors["green"].".".$colors["blue"];
    //国情報ファイルを読み込み
    $f = file("countries/".$targetCountryColor.".txt");
    $IsExistWarData=0;
    for ($i=0;empty($f[$i])===false;$i++){
        $line=explode(",",$f[$i]);
        if(strcmp($line[0], "OwnInfo") == 0){
            $countryName=$line[1];
            $ownStates=$line[2];
            $correction=$line[3];
        }else if(strcmp($line[0], "War") == 0 && strcmp($line[1], $operand[2].$operand[3].$operand[4]."\n") == 0){ //行の末尾の要素を比較するときは改行文字を入れることに注意
            $IsExistWarData=1;
        }else if(strcmp($line[0], "Demands") == 0 && strcmp($line[1], $operand[2].$operand[3].$operand[4]) == 0){
            $demands=$line[2];
        }
    }
    print $countryName.",".$ownStates.",".$correction.",".$colors["red"].",".$colors["green"].",".$colors["blue"].",".$IsExistWarData.",".$demands;
}else if( strcmp($command[0], "demandState") == 0 ){
    $operand = explode(",",$command[1]);
    //色を取得
    $image = imagecreatefrompng("map.png");
    $rgb = imagecolorat($image, $operand[0], $operand[1]);
    $colors = imagecolorsforindex($image, $rgb);
    $targetCountryColor = $colors["red"].".".$colors["green"].".".$colors["blue"];
    //国情報ファイルを読み込み
    $f = file("countries/".$targetCountryColor.".txt");
    for ($i=0;empty($f[$i])===false;$i++){
        $line=explode(",",$f[$i]);
        if(strcmp($line[0], "Demands") == 0 && strcmp($line[1], $operand[2].".".$operand[3].".".$operand[4]) == 0){
            $line[2]=$line[2]-1;
            $f[$i]=implode(",",$line)."\n";
        }else if(strcmp($line[0], "ControllStateNum") == 0){
            $line[1]=$line[1]-1;
            $f[$i]=implode(",",$line)."\n";
        }
    }
    $color = imagecolorallocate($image, $operand[2], $operand[3], $operand[4]);
    imagefill ( $image , $operand[0] , $operand[1] , $color );
    imagepng($image,"map.png"); 
    file_put_contents("countries/".$targetCountryColor.".txt",$f);

    print "Success";
}elseif( strcmp($command[0], "getDate") == 0 ){ //日時取得
    $fp = fopen("turn.txt","r");
    $data = fgetcsv($fp);
    fclose($fp);
    $result = implode(',' , $data);
    print $result;
}elseif( strcmp($command[0], "declareWar") == 0 ){ //宣戦布告
    $operand = explode(",",$command[1]);

    //war.txtに追加
    $fp = fopen("war.txt","a");
    fwrite($fp,$operand[0].",".$operand[1].",0,".$operand[2].",".$operand[3].",".$operand[4]."\n"); //国RGB,国RGB,戦闘前占領値,戦争開始年,月,日
    fclose($fp);

    //宣戦国ファイルを編集
    $filename="countries/".$operand[0].".txt";
    $fp = fopen($filename,"r+");
    $IsExistJoinWarNumData = 0;
    $i=0;
    while(feof($fp)===false){
        $Loadedline = fgetcsv($fp);
        if(strcmp($Loadedline[0], "JoinWarNum") == 0){
            $IsExistJoinWarNumData = 1;
            $JoinWarNum=$Loadedline[1]+1; //参加中の戦争の数に1足す
            $file = file($filename);
            unset($file[$i]); //その行全体を削除
            file_put_contents($filename, $file);
        }
        $i=$i+1;
    }
    fclose($fp);
    $fp = fopen($filename,"a");
    if($IsExistJoinWarNumData == 0) fwrite($fp,"JoinWarNum,1"."\n");
    else fwrite($fp,"JoinWarNum,".$JoinWarNum."\n");
    fwrite($fp,"War,".$operand[1]."\n");
    fclose($fp);

    //防衛国ファイルを編集
    $filename="countries/".$operand[1].".txt";
    $fp = fopen($filename,"r+");
    $IsExistJoinWarNumData = 0;
    $i=0;
    while(feof($fp)===false){
        $Loadedline = fgetcsv($fp);
        if(strcmp($Loadedline[0], "JoinWarNum") == 0){
            $IsExistJoinWarNumData = 1;
            $JoinWarNum=$Loadedline[1]+1; //参加中の戦争の数に1足す
            $file = file($filename);
            unset($file[$i]); //その行全体を削除
            file_put_contents($filename, $file);
        }
        $i=$i+1;
    }
    fclose($fp);
    $fp = fopen($filename,"a");
    if($IsExistJoinWarNumData == 0) fwrite($fp,"JoinWarNum,1"."\n");
    else fwrite($fp,"JoinWarNum,".$JoinWarNum."\n");
    fwrite($fp,"War,".$operand[0]."\n");
    fclose($fp);

    //ニュース
    $fp = fopen("news.txt","a");
    fwrite($fp,$operand[5]."が".$operand[6]."に宣戦布告しました\n");
    fclose($fp);

    print "Success,";
}else{
    print "Failed,notFoundCommand";
}
?>