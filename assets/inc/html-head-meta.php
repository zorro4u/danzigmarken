<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <?php if (empty($_SESSION['su'])) include_once __DIR__."/mato.php"; ?>

    <meta http-equiv="cache-control" content="<?=$cache?>" >
    <meta http-equiv="Expires" content="<?=$expires?>" >
    <meta http-equiv="language" content="DE">

    <!-- Favicons -->
    <link rel="shortcut icon" href="/favicon.ico?v=2" type="image/x-icon">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">
    <link rel="manifest" href="/assets/favicons/manifest.json">
    <link rel="icon" href="/assets/favicons/favicon-16x16.png" type="image/png" sizes="16x16">
    <link rel="icon" href="/assets/favicons/favicon-32x32.png" type="image/png" sizes="32x32">
    <link rel="icon" href="/assets/favicons/android-chrome-192x192.png"
        type="image/png" sizes="192x192">
    <link rel="icon" href="/assets/favicons/android-chrome-512x512.png"
        type="image/png" sizes="512x512">

    <!-- Bootstrap core CSS; header-menu, tabs -->
    <link href="/assets/bootstrap3/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/bootstrap3/css/bootstrap-theme.min.css" rel="stylesheet">

    <!-- Custom styles; Symbole laden -->
    <link href="/assets/fontawesome/css/fontawesome.min.css" rel="stylesheet" >
    <link href="/assets/fontawesome/css/solid.min.css" rel="stylesheet" >

    <link href="/assets/css/stamps.css" rel="stylesheet">
    <link href="/assets/css/style-auth.css" rel="stylesheet">
    <link href="/assets/css/style-header.css" rel="stylesheet">
    <link href="/assets/css/style-blue.css" rel="stylesheet">
    <link href="/assets/css/print.css" rel="stylesheet" type="text/css" media="print" >

    <!-- SEO Angaben, + robots.txt, X-Robots-Tag -->
    <?=$canonical?>
    <meta name="description" content="Paketkarten aus dem Sammelgebiet der Stadt Danzig (1889-1920-1939-1945), Kategorien: Korkstempel, Lochung, Handentwertung, Nachzahlung Porto (Nach-Porto) sind in meiner Sammlung erfasst.">
    <meta name="robots" content="<?=$robots?> noimageindex, max-snippet:200, max-image-preview:standard, unavailable_after:2040-12-31">
    <meta name="google" content="<?=$google?>" >
    <meta name="googlebot" content=" ">
    <meta name="rating" content=" ">
    <meta name="AUTHOR" content=" ">

    <title><?=$title?></title>

<style>
</style>

</head>
