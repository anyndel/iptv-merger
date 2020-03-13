<?php

$users = [
    0 => "ilyaflor@gmail.com",
    1 => "ilya.florenskiy@gmail.com",
    2 => "seyambra69@gmail.com",
    3 => "ambra.frugotti@gmail.com"
];

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

session_start();

$lists = [
    0 => new IPTVSource('IPTV The Best','http://srv19648.noip.network:80/get.php?username=xaosssfz&password=zKjhflgjKLK&type=m3u_plus&output=mpegts',''),
    1 => new IPTVSource('Wirplex','http://cdn-w.cc/get.php?username=3SC8541&password=98326615&output=ts&type=m3u_plus','[w]')
];

$stage = 0;

if ( isset($_POST['stage']))
{
    $stage = $_POST['stage'];
}

if ( isset($_POST['idtoken'] ))
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/tokeninfo?id_token=".$_POST["idtoken"]);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    
    $err = curl_error($ch);

    $json = json_decode($result);

    
    if ( isset($json->email) && in_array($json->email,$users))    
        $_SESSION["APIKEY"] = $json->email;

    curl_close($ch);
    
    header('Location: http://www.cuddlycatlady.com/aside/iptv/index.php');
}

?>
<html>
    <head>
        <title>
            Aggregatore di liste IPTV
        </title>
    </head>
    <body>
        <?php if (!isset($_SESSION["APIKEY"]) || in_array($_SESSION["APIKEY"],$users) === FALSE){ ?>            

            <script src="https://apis.google.com/js/platform.js" async defer></script>
            <meta name="google-signin-client_id" content="608891321747-4ed10f12a76ov1b3mknf104gukj2ebrr.apps.googleusercontent.com"> 
            <div class="g-signin2" data-onsuccess="onSignIn"></div>
            <script>
                async function onSignIn(googleUser) {
                            
                            var id_token = googleUser.getAuthResponse().id_token;
                            console.log("signed in");
                            var formData = new FormData();
                            formData.append('idtoken',id_token);
                            var result = await fetch('http://www.cuddlycatlady.com/aside/iptv/index.php',
                                {
                                    method: "POST",
                                    body: formData
                                }
                            );                             

                            if ( result.ok ){
                                
                                window.location.reload(true);
                            }
                        }
            </script>        
        <?php 
        
       
      
        return;
        }
        ?>

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
                if (curl_errno($ch)){
                    $err = [];
                    $err['error'] = curl_error($ch);
                    $err["code"] = curl_errno($ch);
                	var_dump($err);
                }                
                curl_close($ch);                
            }   
            
            foreach($listData as $remote){
                $len = strlen($remote->remote);

                if ( $len > 0 ){

                    if ( file_exists(getLocalPath($remote->index)) ){
                        $remote->changed = filesize(getLocalPath($remote->index)) != $len;
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

                $keys = [];

                foreach (scandir('./keys') as $key){

                    if (strlen($key) < 6) continue;

                    $keyName = substr($key, 0, strlen($key) - 3);

                    $keys[$keyName] = file_get_contents('./keys/'.$key);
                }

                $output = fopen("./list/list.m3u","w");
                foreach ($lists as $idx=>$source){
                    if ( file_exists('./data'.$idx.'.json')){
                        $data = json_decode(file_get_contents('./data'.$idx.'.json'));

                        //make a new local
                        file_put_contents(getLocalPath($idx),$data->remote);

                        if ($data->finalPrefix != ''){
                            /*
                            $len = strlen($data->remote);
                            $dr = 0;
                            $newOutput = '';
                            $next = 0;
                            $offset = 0;
                            $fwd = 0;
                            for ($i = 0; $i < $len; $i+=$fwd){                                

                                $next = strpos($data->remote,"\n",$offset);
                                $chunk = '';
                                if ($next === FALSE ){
                                    $chunk = substr($data->remote,$offset);
                                    $next = $offset;
                                }
                                else{
                                    $chunk = substr($data->remote,$offset,$next + 2);                                    
                                }

                                $tgt = strpos($chunk,'group-title="');
                                if ( $tgt !== FALSE )
                                    $chunk = substr($chunk,0,$tgt).'group-title="'.$data->finalPrefix.' '.substr($chunk, $tgt+13);
                                //str_replace('group-title="','group-title="'.$data->finalPrefix.' ', $chunk);

                                $newOutput.=$chunk;
                                $fwd = $next - $offset;
                                
                                $fwd = $fwd == 0 ? 1 : $fwd;
                                $offset = $next + 2;
                            }

                            $data->remote = $newOutput;
                            */

                            $rep = 'group-title="'.$data->finalPrefix.' ';

                            $data->remote = preg_replace('/group\-title=\"/',$rep,$data->remote);
                        }

                        foreach($keys as $keyname=>$keyval)
                            $data->remote = preg_replace("/$keyval/",'|'.$keyname.'|',$data->remote);

                        $data->remote = preg_replace('/^http/m','http://cuddlycatlady.com/aside/iptv/get.php?r=http', $data->remote);

                        $break = $idx == 0 ? "" : "\n";

                        fwrite($output,$break.$data->remote);
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