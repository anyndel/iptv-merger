<?php
class IPTVSource{

    public function __construct($name, $url, $prefix)
    {
        $this->$name = $name;
        $this->$url = $url;
        $this->$prefix = $prefix;
    }

    public $name = 'IPTV';
    public $url = 'localhost';
    public $prefix = '';
}

class IPTVFetchResult{

    public function __construct($index){
        $this->$index = $index;
    }

    public $index = -1;
    public $remote = "";
    public $output = "";
    public $changed = false;
}

function getLocalPath($idx){
    return './local/list'.$idx.'.m3u';
}

$lists = [
    0 => new IPTVSource('IPTV The Best','http://srv19648.noip.network:8080/get.php?username=xaosssfz&password=eAjl37u0V3&type=m3u&output=mpegts',''),
    1 => new IPTVSource('Wirplex','http://cdn-w.cc/get.php?username=3SC8541&password=0065269238&output=ts&type=m3u_plus','[w]')
];

$stage = 0;

if ( isset($_POST['stage']))
{
    $stage = $_POST['stage'];
}

?>
<html>
    <head>
        <title>
            Aggregatore di liste IPTV
        </title>
    </head>
    <body>

        <?php if ($stage == 0){ ?>

            <p>Puoi aggiornare le seguenti liste:</p>
            <ul>
                <? foreach( $lists as $list ) { ?>
                    <li>
                        <h4><? echo($list->$name) ?></h4>
                    </li>
                <? } ?>
            </ul>

            Se vuoi, puoi <a href="#" class="action" onclick="actionClick('showConfig')">configurare</a> le opzioni.
            <form action="./index.php" method="post">
                    <div class="config">
                        <? foreach( $lists as $idx=>$list ) { ?>

                            <div>Prefisso lista <strong><? echo($list->$name) ?></strong>:&nbsp;<input type="text" maxlength="4" name="prefix-for-<? echo $idx?>" id="prefix-for-<? echo $idx?>" value="<? echo($list->$prefix)?>" /></div>
                            <div><small>Massimo 4 caratteri. Se vuoto, il prefisso verra` omesso</small></div>
                            
                        <? } ?>
                            <div>Prefisso delle <strong>Novita`</strong>:&nbsp;<input type="text" maxlength="4" name="prefix-for-new" id="prefix-for-new" value="[n]" /></div>
                            <div><small>Massimo 4 caratteri. Se vuoto, il prefisso verra` omesso</small></div>    
                            <input type="hidden" name="stage" value="1"/>                    
                    </div>
                    <input type="submit" name="submit_stage1" value="Recupera le liste"/>
            </form>

        <?php } //stage 0 ?>
        <?php if ($stage == 1){ ?>
        <p>Caricamento delle liste in corso...</p>

        <?
            $listData = array();
            foreach( $lists as $idx=>$list ){
                $nextUrl = $list->$url;                
                $listData[$idx] = new IPTVFetchResult($idx);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$nextUrl);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
                    $listData[$idx]->$remote.= $data;
                    ob_flush();
                    flush();
                    return strlen($data);
                });
                curl_exec($ch);
                curl_close($ch);                
            }   
            
            foreach($listData as $remote){
                $len = strlen($remote->$remote);

                if ( $len > 0 ){

                    if ( file_exists(getLocalPath($remote->$index)) ){
                        $remote->$changed = filesize(getLocalPath($remote->$index)) != $len;
                    }                    
                }
            }

        ?>
        <p>...completato! Questi sono i risultati:</p>
        <ul>
        <?
            foreach($listData as $printable){
                ?><li><strong><?
                echo($lists[$printable->$index]->$name)
                ?></strong>:<?
                if ($printable->$remote == ""){
                    ?> Non e` stato possibile caricare la lista...</li><?
                }
                else
                {
                    ?> La lista e` stata caricata. <?
                    if ($printable->$changed == TRUE)
                    {
                        ?> Sono state trovate modifiche rispetto all'ultima versione caricata!</li><?
                    }
                    else
                    {
                        ?> Non ci sono modifiche rispetto all'ultima versione caricata.</li><?
                    }
                }
            }
        ?>
        </ul>
            <script type="text/javascript">
                function doReset(){
                    let stage = document.querySelector('input[type="hidden"]');
                    stage.value = 0;
                    return true;
                }
            </script>
            <form action="./index.php" method="post">
                <p>Desideri rigenerare il file cumulativo?</p>
                <input type="hidden" name="stage" value="2">
                <input type="submit" name="submit_stage2" value="Rigenera" />
                <input type="submit" name="reset_stage1" value="Annulla" onclick="return doReset();"/>
            </form>

        <?php } //stage 1 ?>

        <?php if ($stage == 2){ ?>
            <p>Rigenerazione della lista in corso...</p>
            
        <?php } //stage 2 ?>

    </body>
</html>