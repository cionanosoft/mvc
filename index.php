<?php
session_start();

require_once(__DIR__.'\\Core\\Composer.php');
include_once(__DIR__.'\\App\\Views\\structure\\header.php');
?>

<div class="loader">
    <img src="img/ajax_loading.gif" alt="Loading...">
</div>

<div class="container">
    <div id="main_div">
        <?php
            include_once(\Core\S_ROUTE::Route()->getView());
        ?>
    </div>
</div>
<?php
include_once(__DIR__.'\\App\\Views\\structure\\footer.php');

