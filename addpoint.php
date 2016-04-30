<?php 
require_once 'config.inc.php';
require_once 'twitteroauth.php';
$cadenaConexion = "host=$pg_serverver port=$pg_port dbname=$pg_dbname user=$pg_user password=$pg_password";

$conexion = pg_connect($cadenaConexion) or die("Error en la Conexion: ".pg_last_error());

if(isset($_REQUEST['x'])){
    $x = $_REQUEST['x'];
}
if(isset($_REQUEST['track_id'])){
    $track_id = $_REQUEST['track_id'];
}
else{
 $track_id = 1;
}
if(isset($_REQUEST['y'])){
    $y = $_REQUEST['y'];
}
if(isset($_REQUEST['t'])){
    $t = $_REQUEST['t'];
}
if(isset($_REQUEST['ax'])){
    $ax = $_REQUEST['ax'];
}
if(isset($_REQUEST['ay'])){
    $ay = $_REQUEST['ay'];
}
if(isset($_REQUEST['az'])){
    $az = $_REQUEST['az'];
}
if(isset($_REQUEST['hueco'])){
    $hueco = $_REQUEST['hueco'];
}


@$sql="INSERT INTO point( x, y, t, track_id, ax, ay, az,hueco )  VALUES ( $x, $y , '$t', $track_id, $ax , $ay , $az , $hueco );"; 

$insert = @pg_query($conexion, $sql);


if ($insert) {
           $resultado[] = array("resultado" => "1", "mensaje" =>  "exito insertando" ); 
           @$sql_r="select lastval();"; 
           $ret_r = pg_query($conexion, $sql_r);
           $row_r = pg_fetch_row($ret_r);

}else{    
             $resultado[] = array("resultado" => "0", "mensaje" =>  "error".pg_last_error($conexion) );    
}

//$insert = @pg_query($conexion, $sql);


 @$sql="SELECT   track_id 
,to_char(t,'YYYY-MM-DD HH24:MI:00') as fecha
 ,count(*)over() as total
  FROM public.point
where ST_Distance(ST_SetSRID(ST_MakePoint( $x, $y),4326),geom )<= $radio
AND  t > now()-'$pasado'::interval
group by track_id
,fecha 
  limit 1"; 

 $ret = pg_query($conexion, $sql);
$row = pg_fetch_row($ret);//print_r($row);
if ( isset($row['2']) && $row['2'] >= $min_point ) { echo "<br>";
      $sql2="SELECT  tweet.tweet_srt
         FROM point
            inner join  tweet on point.point_id = tweet.point_id
        where ST_Distance(ST_SetSRID(ST_MakePoint( $x, $y),4326),geom )<= $radio
            AND  t > now()-'$pasado'::interval
            limit 1 "; 
         $ret2 = pg_query($conexion, $sql2);

            if(!$ret2){ //print_r($ret2);                   

                        define("CONSUMER_KEY", "tdpDQbgkDs4V4GAAcpcoEXSEH");
                        define("CONSUMER_SECRET", "CtEBWsxWOK653rmIMS3uAVx0CHuRPrjWpfc6yW71Bf5RYyvV7g");
                        define("OAUTH_TOKEN", "4821627419-R33c46wOTsgwOQePjqTGs6SwEozSVKWwHpE6LzW");
                        define("OAUTH_SECRET", "UZSTUBPxznsmEQfdwjMba6mKN1RyBaZbuscFiyqJ577ge");

                        $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_SECRET);
                        $content = $connection->get('account/verify_credentials');
                       // print_r( $row_r);
                        $r= $connection->post('statuses/update', array('status' => "Alerta Hueco en la posicion: $x, $y  @SectorMovilidad @Bogota  @EnriquePenalosa @BogotaTransito")); //print_r($r->id_str);
                         $consult="INSERT INTO public.tweet(tweet_srt, point_id) VALUES ($r->id_str,  $row_r[0]); ";
                        $insert = @pg_query($conexion, $consult);

            }

}


echo json_encode($resultado);
pg_close($conexion);
?>