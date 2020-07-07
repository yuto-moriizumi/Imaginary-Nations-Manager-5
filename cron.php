<html>
    what
</html>
<?php
error_reporting(0);
mb_language("ja");
mb_internal_encoding('UTF-8');

function cron() {
    //現実1日で世界線8年進む
    //現実1時間で世界線4ヶ月進む
    //現実15分で世界線1ヶ月進む
    //15分ごとのcronを想定

    //ファイルから日時を読み込む
    $fp = fopen("turn.txt","r");
    $data = fgetcsv($fp);
    fclose($fp);

    //1ヶ月足す
    $data[1] = $data[1]+1;
    if ($data[1] >= 13) { //13ヶ月目になったら
        $data[1] = 1;
        $data[0] = $data[0]+1;
    }

    //ファイルに書き込み
    $fp = fopen("turn.txt","w");
    $line = implode(',' , $data);
	fwrite($fp, $line."\n");
    fclose($fp);

    //戦争処理
    unset($data); //念のため変数を破棄
    $fileWar = file("war.txt");

    for($line=0; empty($fileWar[$line])===false;$line++){
        $part = explode(",",$fileWar[$line]);
        $filenameAttacker = "countries/".$part[0].".txt";
        $filenameDefender = "countries/".$part[1].".txt";

        $fpAttacker = fopen($filenameAttacker,"r");
        $dataAttacker = fgetcsv($fpAttacker);
        $AttackerMil = $dataAttacker[2]**0.5*$dataAttacker[3]/100;

        $fpDefender = fopen($filenameDefender,"r");
        $dataDefender = fgetcsv($fpDefender);
        $DefenderMil = $dataDefender[2]**0.5*$dataDefender[3]/100;

        fclose($fpAttacker);
        fclose($fpDefender);

        $fAtk = file($filenameAttacker);
        $fDef = file($filenameDefender);

        $IsExistAttackerControllStateNumData = 0;
        $atkJoinWar=1;
        //支配下領土数を取得、存在しなければ領土数に設定
        for($i=0; empty($fAtk[$i])===false;$i++){ //i行が空でなければ
            $loadLine = explode(",",$fAtk[$i]);
            if(strcmp($loadLine[0], "ControllStateNum") == 0){
                $AttackerControllStateNum=$loadLine[1];
                $IsExistAttackerControllStateNumData = 1;
                unset($fAtk[$i]); //ControllStateNumの行を削除
                file_put_contents($filenameAttacker,$fAtk);
            }else if(strcmp($loadLine[0], "JoinWarNum") == 0){ //参戦数を取得
                $atkJoinWar=$loadLine[1];
                if ($atkJoinWar==0) $atkJoinWar=1;//バグ対策、0だったら1にする
            }
        }
        if($IsExistAttackerControllStateNumData == 0) $AttackerControllStateNum = $dataAttacker[2]; //データが無かった場合は領土数に設定

        $IsExistDefenderControllStateNumData = 0;
        $defJoinWar=1;
        //支配下領土数を取得、存在しなければ領土数に設定
        for($i=0; empty($fDef[$i])===false;$i++){ //i行が空でなければ
            $loadLine = explode(",",$fDef[$i]);
            if(strcmp($loadLine[0], "ControllStateNum") == 0){
                $DefenderControllStateNum = $loadLine[1];
                $IsExistDefenderControllStateNumData = 1;
                unset($fDef[$i]); //ControllStateNumの行を削除
                file_put_contents($filenameDefender,$fDef);
            }else if(strcmp($loadLine[0], "JoinWarNum") == 0){ //参戦数を取得
                $defJoinWar=$loadLine[1];
                if ($defJoinWar==0) $defJoinWar=1;//バグ対策、0だったら1にする
            }
        }
        if($IsExistDefenderControllStateNumData == 0) $DefenderControllStateNum = $dataDefender[2]; //データが無かった場合は領土数に設定

        $AttackerMil=round($AttackerMil/$atkJoinWar);
        $DefenderMil=round($DefenderMil/$defJoinWar);
        $Dice = ($AttackerMil - $DefenderMil)*5 + rand(0,100); //0～49で負け、50で引き分け、51～100で勝利
        $combatResult = round(($Dice -50)/10);

        /*
	    	e. 国ファイルの管理下州の数を編集
	    		i.  戦闘占領地=0（引き分け）ならば なにもしない
	    		ii. 戦闘占領値>0 （戦闘に勝利） かつ 戦闘前占領値>=0 （どこも占領されていない）ならば
	    			防衛側の管理下州= 管理下州 - 戦闘占領値
	    		iii.  戦闘占領値>0 （戦闘に勝利） かつ 戦闘前占領値<0 （どこか占領されている）
	    			1) 戦闘占領値>= -1*戦闘前占領値 ならば
	    				a) 宣戦側の管理下州= 管理下州 - 戦闘前占領値（この戦争分の占領地を全部奪還）
	    				b) 防衛側の管理下州= 管理下州 - 戦闘占領値-戦闘前占領値（差分を占領される）
	    			2) 戦闘占領値<  -1*戦闘前占領値 ならば
	    				a) 宣戦側の管理下州= 管理下州 + 戦闘占領値
	    		iv. 戦闘占領値<0 （戦闘に敗北）かつ戦闘前占領値=<0（どこか占領されている)ならば
	    			宣戦側の管理下州= 管理下州 + 戦闘占領値
	    		v. 戦闘占領値<0 （戦闘に敗北）かつ戦闘前占領値>0（どこか占領している)ならば
	    			a) 戦闘占領値 < -1*戦闘前占領値 ならば
	    				a) 宣戦側の管理下州= 管理下州 + 戦闘占領値 + 戦闘前占領値 （差分を占領される）
	    				b) 防衛側の管理下州= 管理下州 + 戦闘前占領値 （この戦争分の占領地を全部奪還）
	    			b) 戦闘占領値>= -1*戦闘前占領値ならば
	    				a) 防衛側の管理下州= 管理下州 - 戦闘占領値
        */

        if ($combatResult>0 && $part[2]>=0) $DefenderControllStateNum=$DefenderControllStateNum-$combatResult;
        else if ($combatResult>0 && $part[2]<0){
            if ($combatResult>= -1*$part[2]){
                $AttackerControllStateNum=$AttackerControllStateNum-$part[2];
                $DefenderControllStateNum=$DefenderControllStateNum-$combatResult-$part[2];
            }else $AttackerControllStateNum=$AttackerControllStateNum+$combatResult;
        }else if($combatResult<0 && $part[2]<=0) $AttackerControllStateNum=$AttackerControllStateNum+$combatResult;
        else if($combatResult<0 && $part[2]>0){
            if($combatResult<-1*$part[2]){
                $AttackerControllStateNum=$AttackerControllStateNum+$combatResult+$part[2];
                $DefenderControllStateNum=$DefenderControllStateNum+$part[2];
            }else $DefenderControllStateNum=$DefenderControllStateNum-$combatResult;
        }

        $fpAttacker = fopen($filenameAttacker,"a");
        fwrite($fpAttacker,"ControllStateNum,".$AttackerControllStateNum."\n");
        fclose($fpAttacker);

        $fpDefender = fopen($filenameDefender,"a");
        fwrite($fpDefender,"ControllStateNum,".$DefenderControllStateNum."\n");
        fclose($fpDefender);

        $part[2]=$part[2]+$combatResult; //戦闘前占領値を書き換え
        $fileWar[$line]=implode(",",$part);
    }
    file_put_contents("war.txt", $fileWar);

    //終戦処理
    $fWar = file("war.txt");
    for($i=0; empty($fWar[$i])===false;$i++){ //劣勢側が敗戦しているか調べる
        $isEndWar[$i]=0;
        $line = explode(",",$fWar[$i]);
        if ($line[2]>=0){ //攻撃側が勝利しているとき
            $fCountry = file("countries/".$line[1].".txt"); //防衛側国ファイルを配列に代入
            for($j=0; empty($fCountry[$j])===false;$j++){
                $lineCountry = explode(",",$fCountry[$j]);
                if(strcmp($lineCountry[0], "ControllStateNum") == 0){ //管理州情報が見つかったら
                    if($lineCountry[1]<=0) $isEndWar[$i]=1; //管理州が0以下だったら終戦
                }
            }
        }else{ //防衛側が勝利している時
            $fCountry = file("countries/".$line[0].".txt"); //攻撃側国ファイルを配列に代入
            for($j=0; empty($fCountry[$j])===false;$j++){
                $lineCountry = explode(",",$fCountry[$j]);
                if(strcmp($lineCountry[0], "ControllStateNum") == 0){ //管理州情報が見つかったら
                    if($lineCountry[1]<=0) $isEndWar[$i]=1; //管理州が0以下だったら終戦
                }
            }
        }
    }
    for($i=0; empty($isEndWar[$i])===false;$i++){
        if($isEndWar[$i]==1){//終戦であれば
            $line = explode(",",$fWar[$i]);
            if($line[2]>=0){ //攻撃側の勝利であれば
                $existsDemands = 0;
                $fCountry = file("countries/".$line[1].".txt"); //防衛側国ファイルをを配列に代入
                for($j=0; empty($fCountry[$j])===false;$j++){
                    $lineCountry = explode(",",$fCountry[$j]);
                    if(strcmp($lineCountry[0], "JoinWarNum") == 0){ //管理州情報が見つかったら
                        $lineCountry[1]=$lineCountry[1]-1; //参戦数を1減らす
                        $fCountry[$j] = implode(",",$lineCountry)."\n";
                    }else if(strcmp($lineCountry[0], "Demands") == 0 && strcmp($lineCountry[1], $line[0]) == 0){
                        $existsDemands = 1;
                        $lineCountry[2]=$lineCountry[2]+round($line[2]/$ownStates*15); //占領値/領土数*15 の要求を追加（最大15）
                        $fCountry[$j] = implode(",",$lineCountry)."\n";
                    }else if(strcmp($lineCountry[0], "ControllStateNum") == 0){
                        $lineCountry[1]=$lineCountry[1]+$line[2]; //占領値を足してこの戦争分の占領地を返還
                        $fCountry[$j] = implode(",",$lineCountry)."\n";
                    }else if(strcmp($lineCountry[0], "OwnInfo") == 0){
                        $lostCountry = $lineCountry[1];
                        $ownStates = $lineCountry[2];
                    }else if(strcmp($lineCountry[0], "War") == 0 && strcmp($lineCountry[1], $line[0]) == 0){
                        unset($fCountry[$j]);
                    }
                }
                file_put_contents("countries/".$line[1].".txt",$fCountry);
                unset($fCountry); //念のため変数をクリア
                if ($existsDemands == 0){
                    $fp = fopen("countries/".$line[1].".txt","a");
                    fwrite($fp,"Demands,".$line[0].",".round($line[2]/$ownStates*15)."\n"); //要求を追加
                    fclose($fp);
                }
                $fCountry = file("countries/".$line[0].".txt"); //攻撃側国ファイルをを配列に代入
                $lineCountry = explode(",",$fCountry[0]);
                $wonCountry = $lineCountry[1];
                for($j=0; empty($fCountry[$j])===false;$j++){
                    $lineCountry = explode(",",$fCountry[$j]);
                    if(strcmp($lineCountry[0], "JoinWarNum") == 0){ //管理州情報が見つかったら
                        $lineCountry[1]=$lineCountry[1]-1; //参戦数を1減らす
                        $fCountry[$j] = implode(",",$lineCountry)."\n";
                    }else if(strcmp($lineCountry[0], "War") == 0 && strcmp($lineCountry[1], $line[0]) == 0){
                        unset($fCountry[$j]);
                    }
                }
                file_put_contents("countries/".$line[0].".txt",$fCountry);
            }else{ //防衛側の勝利であれば
                $existsDemands = 0;
                $fCountry = file("countries/".$line[0].".txt"); //攻撃側国ファイルをを配列に代入
                for($j=0; empty($fCountry[$j])===false;$j++){
                    $lineCountry = explode(",",$fCountry[$j]);
                    if(strcmp($lineCountry[0], "JoinWarNum") == 0){ //管理州情報が見つかったら
                        $lineCountry[1]=$lineCountry[1]-1; //参戦数を1減らす
                        $fCountry[$j] = implode(",",$lineCountry)."\n";
                    }else if(strcmp($lineCountry[0], "Demands") == 0 && strcmp($lineCountry[1], $line[1]) == 0){
                        $existsDemands = 1;
                        $lineCountry[2]=$lineCountry[2]+round(-1*$line[2]/$ownStates*15); //占領値/領土数*15 の要求を追加（最大15）
                        $fCountry[$j] = implode(",",$lineCountry)."\n";
                    }else if(strcmp($lineCountry[0], "ControllStateNum") == 0){
                        $lineCountry[1]=$lineCountry[1]-$line[2]; //占領値を足してこの戦争分の占領地を返還（占領値は負の値であることに注意）
                        $fCountry[$j] = implode(",",$lineCountry)."\n";
                    }else if(strcmp($lineCountry[0], "OwnInfo") == 0){
                        $lostCountry = $lineCountry[1];
                        $ownStates = $lineCountry[2];
                    }else if(strcmp($lineCountry[0], "War") == 0 && strcmp($lineCountry[1], $line[0]) == 0){
                        unset($fCountry[$j]);
                    }
                }
                file_put_contents("countries/".$line[0].".txt",$fCountry);
                if ($existsDemands == 0){
                    $fp = fopen("countries/".$line[0].".txt","a");
                    fwrite($fp,"Demands,".$line[1].",".round(-1*$line[2]/$ownStates*15)."\n"); //要求を追加
                    fclose($fp);
                }
                $fCountry = file("countries/".$line[1].".txt"); //防衛側国ファイルをを配列に代入
                $lineCountry = explode(",",$fCountry[0]);
                $wonCountry = $lineCountry[1];
                for($j=0; empty($fCountry[$j])===false;$j++){
                    $lineCountry = explode(",",$fCountry[$j]);
                    if(strcmp($lineCountry[0], "JoinWarNum") == 0){ //管理州情報が見つかったら
                        $lineCountry[1]=$lineCountry[1]-1; //参戦数を1減らす
                        $fCountry[$j] = implode(",",$lineCountry)."\n";
                    }else if(strcmp($lineCountry[0], "War") == 0 && strcmp($lineCountry[1], $line[0]) == 0){
                        unset($fCountry[$j]);
                    }
                }
                file_put_contents("countries/".$line[1].".txt",$fCountry);
            }
            unset($fWar[$i]);

            //ニュース
            $fp = fopen("news.txt","a");
            fwrite($fp,$wonCountry."が".$lostCountry."を倒しました！\n");
            fclose($fp);
        }
    }
    file_put_contents("war.txt",$fWar);
}

cron();

?>