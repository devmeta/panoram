<?php 

class CustomDatabase extends Adminer {
  function head() {
  	echo '
    <link href="images/iso-verusados.png" rel="shortcut icon" type="image/x-icon">
    <link href="images/iso-verusados-big.png" rel="apple-touch-icon">    
  	<link href="css/select2.min.css" type="text/css" rel="stylesheet" />
  	<link href="css/style.css" type="text/css" rel="stylesheet" />
  	<script type="text/javascript" src="js/jquery.min.js"></script>
  	<script type="text/javascript" src="js/select2.full.js"></script>
	<script type="text/javascript">
		$(function(){
			$("select").select2();	
		})
	</script>';

  }

  function database() {
    return 'verusados';
  }
}