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
    //国情報ファイルを読み込み
    $filename = "countries/".$colors["red"].".".$colors["green"].".".$colors["blue"].".txt";
    if (file_exists($filename)){ //ファイルが開けたら
        $fp= fopen($filename,"r");
        $data = fgetcsv($fp);
        fclose($fp);
        //色を塗る
        /* 色塗り処理は一時停止
        $image = imagecreatefrompng("map.png");
        $color = imagecolorallocate($image, 255, 0, 0);
        imagefill ( $image , $operand[0] , $operand[1] , $color );
        imagepng($image,"tempMap.png");
        */
        print "Success,".$data[1].",".$data[2].",".$data[3].",".$colors["red"].",".$colors["green"].",".$colors["blue"];
    }else print "Failed,国情報ファイルが見つかりません：".$filename;

}elseif( strcmp($command[0], "addStates") == 0 ){ //州の追加
    $operand = explode(",",$command[1]);
    //色を取得
    $image = imagecreatefrompng("map.png");
    $rgb = imagecolorat($image, $operand[0], $operand[1]);
    $colors = imagecolorsforindex($image, $rgb);
    //領有国を確認
    $filename = "countries/".$colors["red"].".".$colors["green"].".".$colors["blue"].".txt";
    $fp= fopen($filename,"r");
    $data = fgetcsv($fp);
    fclose($fp);
    if(strcmp($data[1], "NML") == 0){ //無主地だった時
        //地図を編集
        $color = imagecolorallocate($image, $operand[2], $operand[3], $operand[4]);
        imagefill ( $image , $operand[0] , $operand[1] , $color );
        imagepng($image,"map.png"); 
        //国情報ファイルから一行目を取得
        $filename = "countries/".$operand[2].".".$operand[3].".".$operand[4].".txt";
        $fp = fopen($filename, "r");
        $data = fgetcsv($fp);
        fclose($fp); 
        //国情報ファイルの一行目（その国の情報が書かれている）を削除
        $file = file($filename);
        unset($file[1]);
        file_put_contents($filename, $file);
        //州の数を追加
        $data[2]=$data[2]+1;
        //国情報ファイルの一行目を追加
        $fp = fopen($filename, "r+");
        fwrite($fp,"OwnInfo,".$data[1].",".$data[2].",".$data[3].",".$data[4]."\n");
        fclose($fp);
        print "Success,";
    }else{
        //元領有国の領有数を1減らす
        $fOwner=file($filename);
        $line=explode(",",$fOwner[0]); //1行目を分割
        $line[2]=$line[2]-1;//領有マス数を1つ減らす
        $fOwner[0]=implode(",",$line)."\n";
        file_put_contents($filename,$fOwner);

        //地図を編集
        $color = imagecolorallocate($image, $operand[2], $operand[3], $operand[4]);
        imagefill ( $image , $operand[0] , $operand[1] , $color );
        imagepng($image,"map.png"); 
        //国情報ファイルから一行目を取得
        $filename = "countries/".$operand[2].".".$operand[3].".".$operand[4].".txt";
        $fp = fopen($filename, "r");
        $data = fgetcsv($fp);
        fclose($fp); 
        //国情報ファイルの一行目（その国の情報が書かれている）を削除
        $file = file($filename);
        unset($file[1]);
        file_put_contents($filename, $file);
        //州の数を追加
        $data[2]=$data[2]+1;
        //国情報ファイルの一行目を追加
        $fp = fopen($filename, "r+");
        fwrite($fp,"OwnInfo,".$data[1].",".$data[2].",".$data[3].",".$data[4]."\n");
        fclose($fp);
        print "Success,";
    }
}elseif( strcmp($command[0], "makeCountry") == 0 ){ //国の作成
    $operand = explode(",",$command[1]);

    //色を取得
    $image = imagecreatefrompng("map.png");
    $rgb = imagecolorat($image, $operand[0], $operand[1]);
    $colors = imagecolorsforindex($image, $rgb);
    //領有国を確認
    $filename = "countries/".$colors["red"].".".$colors["green"].".".$colors["blue"].".txt";
    $fOwner=file($filename);
    $line=explode(",",$fOwner[0]); //1行目を分割
    $line[2]=$line[2]-1;//領有マス数を1つ減らす
    $fOwner[0]=implode(",",$line)."\n";
    file_put_contents($filename,$fOwner);

    //国情報ファイルを作成
    $filename = "countries/".$operand[3].".".$operand[4].".".$operand[5].".txt"; //RGB.txt
    if (file_exists($filename) === true){ //ファイルが存在すれば
        print "Failed,同じ色を使用している国が既に存在します";
    }else{
        $fp = fopen($filename, "w");
        fwrite($fp, "OwnInfo,".$operand[2].",1,".$operand[6].",0\n"); //国名,領土数,軍事力補正値,戦争参加数
        fclose($fp);
        //色を塗る
        $image = imagecreatefrompng("map.png");
        $color = imagecolorallocate($image, $operand[3], $operand[4], $operand[5]);
        imagefill ( $image , $operand[0] , $operand[1] , $color );
        imagepng($image,"map.png");
        print "Success,";
    }
}elseif( strcmp($command[0], "getDate") == 0 ){ //日時取得
    $fp = fopen("turn.txt","r");
    $data = fgetcsv($fp);
    fclose($fp);
    $result = implode(',' , $data);
    print $result;
}else{
    print "Failed,notFoundCommand";
}
?>