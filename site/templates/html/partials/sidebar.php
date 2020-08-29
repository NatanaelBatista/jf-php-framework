<v-navigation-drawer
  v-model   = "show_menu"
  fixed
  app
>
  <v-toolbar flat class="transparent">
    <v-list class="pa-0">
      <v-list-item>
        <v-list-item-content>
          <v-list-item-title>
            <strong>JF PHP Framework</strong>
          </v-list-item-title>
        </v-list-item-content>
      </v-list-item>
    </v-list>
  </v-toolbar>

  <v-list class="pt-0">
    <v-divider></v-divider>
    <?php foreach ( $this->data->modules as $mod_name => $module ) { ?>
      <v-list-group
        no-action
      >
        <template v-slot:activator>
          <v-list-item>
            <v-list-item-title><?= $module->label ?></v-list-item-title>
          </v-list-item>
        </template>
        <?php foreach ( $module->pages as $pg_name => $page ) { ?>
          <v-list-item href="<?= URL_PAGES . "/$mod_name/$pg_name.html" ?>">
            <v-list-item-content class="primary--text"><?= $page ?></v-list-item-content>
          </v-list-item>
        <?php } ?>
      </v-list-group>
    <?php } ?>
  </v-list>
</v-navigation-drawer>
