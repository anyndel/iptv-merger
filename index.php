<?php
class IPTVSource{

    public function __construct($name, $url, $prefix)
    {
        $this->name = $name;
        $this->url = $url;
        $this->prefix = $prefix;
    }

    public $name = 'IPTV';
    public $url = 'localhost';
    public $prefix = '';
}

class IPTVFetchResult{

    public function __construct($index){
        $this->index = $index;
    }

    public $index = -1;
    public $remote = "";
    public $changed = false;
    public $finalPrefix = '';
}

function getLocalPath($idx){
    return './local/list'.$idx.'.m3u';
}

$lists = [
    0 => new IPTVSource('IPTV The Best','http://srv19648.noip.network:8080/get.php?username=xaosssfz&password=zKjhflgjKLK&type=m3u_plus&output=mpegts',''),
    1 => new IPTVSource('Wirplex','http://cdn-w.cc/get.php?username=3SC8541&password=98326615&output=ts&type=m3u_plus','[w]')
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
                <?php foreach( $lists as $list ) { ?>
                    <li>
                        <h4><?php echo($list->name) ?></h4>
                    </li>
                <?php } ?>
            </ul>

            Se vuoi, puoi <a href="#" class="action" onclick="actionClick('showConfig')">configurare</a> le opzioni.
            <form action="./index.php" method="post">
                    <div class="config">
                        <?php foreach( $lists as $idx=>$list ) { ?>

                            <div>Prefisso lista <strong><?php echo($list->name) ?></strong>:&nbsp;<input type="text" maxlength="4" name="prefix-for-<?php echo $idx?>" id="prefix-for-<?php echo $idx?>" value="<?php echo($list->prefix)?>" /></div>
                            <div><small>Massimo 4 caratteri. Se vuoto, il prefisso verra` omesso</small></div>
                            
                        <?php } ?>
                            <div>Prefisso delle <strong>Novita`</strong>:&nbsp;<input type="text" maxlength="4" name="prefix-for-new" id="prefix-for-new" value="[n]" /></div>
                            <div><small>Massimo 4 caratteri. Se vuoto, il prefisso verra` omesso</small></div>    
                            <input type="hidden" name="stage" value="1"/>                    
                    </div>
                    <input type="submit" name="submit_stage1" value="Recupera le liste"/>
            </form>

        <?php } //stage 0 ?>
        <?php if ($stage == 1){ ?>
        <p>Caricamento delle liste in corso...</p>

        <?php
            $listData = [];
            foreach( $lists as $idx=>$list ){
                $nextUrl = $list->url;                
                $listData[$idx] = new IPTVFetchResult($idx);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$nextUrl);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
                    echo($data);
                    flush();
                    return strlen($data);
                });
                ob_start();
                curl_exec($ch);
                $listData[$idx]->remote = ob_get_clean();
                curl_close($ch);                
            }   
            
            foreach($listData as $remote){
                $len = strlen($remote->remote);

                if ( $len > 0 ){

                    if ( file_exists(getLocalPath($remote->index)) ){
                        $remote->$changed = filesize(getLocalPath($remote->index)) != $len;
                    }  
                    
                    if ( isset($_POST['prefix-for-'.$remote->index]) )
                        $remote->finalPrefix = $_POST['prefix-for-'.$remote->index];
                    else
                        $remote->finalPrefix = $lists[$remote->index]->prefix;
                }                

                file_put_contents('./data'.$remote->index.'.json',json_encode($remote));
            }

        ?>
        <p>...completato! Questi sono i risultati:</p>
        <ul>
        <?php
            foreach($listData as $printable){
                ?><li><strong><?php
                echo($lists[$printable->index]->name)
                ?></strong>:<?php
                if ($printable->remote == ""){
                    ?> Non e` stato possibile caricare la lista...</li><?php
                }
                else
                {
                    ?> La lista e` stata caricata. <?php
                    if ($printable->changed == TRUE)
                    {
                        ?> Sono state trovate modifiche rispetto all'ultima versione caricata!</li><?php
                    }
                    else
                    {
                        ?> Non ci sono modifiche rispetto all'ultima versione caricata.</li><?php
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

            <?php



                $output = fopen("./list/list.m3u","w");
                foreach ($lists as $idx=>$source){
                    if ( file_exists('./data'.$idx.'.json')){
                        $data = json_decode(file_get_contents('./data'.$idx.'.json'));

                        if ($data->finalPrefix != ''){
                            $len = strlen($data->remote);
                            
                            $newOutput = '';
                            $next = 0;
                            for ($i = 0; $i < $len; $i+=$next){
                                
                                $next = strpos($data->remote,"\n");
                                $chunk = '';
                                if ($next === FALSE ){
                                    $chunk = $data->remote;
                                    $next = $len;
                                }
                                else{
                                    $chunk = substr($data->remote,0,$next + 2);                                    
                                }

                                str_replace('group-title="','group-title="'.$data->finalPrefix.' ', $chunk);

                                $newOutput.=$chunk;

                                $data->remote = substr($data->remote, $next + 1 );
                            }

                            $data->remote = $newOutput;
                        }

                        fwrite($output,$data->remote);
                    }
                    else
                        continue;
                }
                fclose($output);
            ?>
            <p>...Creazione completata!</p>

        <?php } //stage 2 ?>

    </body>
</html>