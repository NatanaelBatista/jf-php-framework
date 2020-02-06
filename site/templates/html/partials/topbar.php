<v-toolbar
  class   = "black"
  role    = 'banner'
  app
  dark
  fixed
  extended
  dense
>
  <v-tooltip bottom>
    <v-toolbar-title
      @click  = "location.href=url.base"
      class   = "clickable"
      slot    = "activator"
    >
      <?= $this->data->app_name ?> - <?= $this->data->module ?>
      <span class="hidden-sm-and-down">
        |
        <small class="light-blue--text text--lighten-2"><?= $this->data->title ?></small>
      </span>
    </v-toolbar-title>
    Ir para a página inicial
  </v-tooltip>
  </v-tooltip>
  <v-spacer></v-spacer>
  <div slot="extension">
    <v-toolbar-side-icon @click="show_menu = true">
      <v-tooltip bottom>
        <v-icon slot="activator">menu</v-icon>
        <span>Exibir seletor de módulos</span>
      </v-tooltip>
    </v-toolbar-side-icon>
  </div>
</v-toolbar>
