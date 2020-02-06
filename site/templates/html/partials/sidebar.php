<v-navigation-drawer
  v-model   = "show_menu"
  fixed
  app
>
  <v-toolbar flat class="transparent">
    <v-list class="pa-0">
      <v-list-tile avatar>
        <v-list-tile-avatar>
          <img src="https://randomuser.me/api/portraits/men/85.jpg">
        </v-list-tile-avatar>

        <v-list-tile-content>
          <v-list-tile-title>John Leider</v-list-tile-title>
        </v-list-tile-content>
      </v-list-tile>
    </v-list>
  </v-toolbar>

  <v-list class="pt-0">
    <v-divider></v-divider>
    <?php foreach ( $this->data->modules as $mod_name => $module ) { ?>
      <v-list-group
        prepend-icon   = "<?= $module->icon ?>"
        no-action
      >
        <template v-slot:activator>
          <v-list-tile>
            <v-list-tile-title><?= $module->label ?></v-list-tile-title>
          </v-list-tile>
        </template>
        <?php foreach ( $module->pages as $pg_name => $page ) { ?>
          <v-list-tile href="<?= URL_PAGES . "/$mod_name/$pg_name.html" ?>">
            <v-list-tile-content class="primary--text"><?= $page ?></v-list-tile-content>
          </v-list-tile>
        <?php } ?>
      </v-list-group>
    <?php } ?>
  </v-list>
</v-navigation-drawer>