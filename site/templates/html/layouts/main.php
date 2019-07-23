<?php
  $this->set( 'modules', \JF\Config::get( 'ui.menu' ) );
?>

<!DOCTYPE html>
<html lang = 'pt-Br'>
  <head>
    <title>
      <?= $this->data->title ?> - <?= $this->data->module ?> | <?= $this->data->app_name ?>
    </title>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="theme-color" content="<?= $this->data->theme_color ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui" />
    <meta name="keywords" content="<?= $this->data->keywords ?>" />
    <meta name="description" content="<?= $this->data->description ?>" />
    <meta name="author" content="<?= $this->data->author ?>" />
    
    <link href="<?= $this->ui( 'js/manifest.json' ) ?>" rel="manifest" />
    <link href="<?= $this->ui( 'img/favicon.png' ) ?>" rel="shortcut" type='image/png' />
    <link href="<?= $this->ui( 'img/favicon.png' ) ?>" rel="shortcut icon" type='image/png' />
    <link href="<?= $this->ui( 'vendors/vuetify/vuetify.min.css' ) ?>" rel="stylesheet" type="text/css" />
    <link href="<?= $this->ui( 'vendors/google/md/googlefonts.min.css' ) ?>" rel="stylesheet" type="text/css" />
    <?= $this->css( 'css/jf.css' ) ?>
    <script src="<?= $this->ui( 'vendors/vuejs/vue.min.js' ) ?>"></script>
    <script src="<?= $this->ui( 'vendors/vuetify/vuetify.min.js' ) ?>"></script>
  </head>
  <body>
    <div id="app">
      <v-app>
        <?= $this->partial( 'topbar', true ) ?>
        <?= $this->partial( 'sidebar', true ) ?>
        <v-content>
          <v-container fluid><?= $this->content() ?></v-container>
        </v-content>
      </v-app>
    </div>
    <script src="<?= URL_UI ?>/vendors/axios/axios.min.js"></script>
    <?= $this->data( 'data' ) ?>
    <?= $this->js( 'js/jf.js' ) ?>
    <?= $this->js( 'js/app.js' ) ?>
    <?= $this->js( 'controller.js', true ) ?>
  </body>
</html>
