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

$lists = [
    new IPTVSource('IPTV The Best','http://srv19648.noip.network:8080/get.php?username=xaosssfz&password=eAjl37u0V3&type=m3u&output=mpegts',''),
    new IPTVSource('Wirplex','http://cdn-w.cc/get.php?username=3SC8541&password=0065269238&output=ts&type=m3u_plus','[w]')
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
        ?>

        <?php } //stage 1 ?>

        <?php if ($stage == 2){ ?>

        <?php } //stage 2 ?>

    </body>
</html>