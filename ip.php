<?php

$users = [
    0 => "ilyaflor@gmail.com",
    1 => "ilya.florenskiy@gmail.com",
    2 => "seyambra69@gmail.com",
    3 => "ambra.frugotti@gmail.com"
];

session_start();

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
    
    header('Location: http://www.cuddlycatlady.com/aside/iptv/ip.php');
}
?>
<html>
    <head>
        <title>
            Aggregatore di liste IPTV: Aggiorna IP
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
                            var result = await fetch('http://www.cuddlycatlady.com/aside/iptv/ip.php',
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
        
        
        

        if ( isset($_POST['submit_home']) || isset($_POST['submit_ambra']))
        {
            

            $file = isset($_POST['submit_home']) ? "home.ip" : "ambra.ip";
            $ip = $_SERVER["REMOTE_ADDR"];
            file_put_contents('./whitelisted/'.$file,$ip);
            ?>
            <h3>Aggiornato con successo! O:</h3>
            <?php
        }
        else
        {
            

        ?>
        <form method="POST">
            <input type="submit" name="submit_home" value="Imposta IP di casa" /><br /><input type="submit" name="submit_ambra" value="Imposta IP del cellulare" />
        </form>
        <?php
        }        
        ?>
    </body>
</html>