const vueMessages = {
  pt_BR : {
    $vuetify : {
      close: 'Fechar',
      badge   : 'crachá',
      dataIterator: {
        pageText: '{0}-{1} de {2}',
        noResultsText: 'nenhum resultado encontrado',
        loadingText: 'Carregando...',
      },
      dataTable: {
        itemsPerPageText: 'Registros por página:',
        ariaLabel: {
          sortDescending: ': Ordenação decrescente. Ative para remover a ordenação.',
          sortAscending: ': Ordenação crescente. Ative para deixar em ordem decrescente.',
          sortNone: ': Sem ordenação. Ative para deixar em ordem crescente.',
        },
      },
      dataFooter: {
        itemsPerPageText: 'itens por página:',
        itemsPerPageAll: 'Tudo',
        nextPage: 'Próxima página',
        prevPage: 'Página anterior',
        firstPage: 'Primeira página',
        lastPage: 'Última página',
      },
      datePicker: {
        itemsSelected: '{0} selecionado(s)',
      },
      noDataText: 'Nenhum dado disponível',
      carousel: {
        prev: 'Slide anterior',
        next: 'Próximo slide',
      },
      calendar: {
        moreEvents: 'Mais {0}',
      },
    },
  },
};

const i18n = new VueI18n({
  locale: 'pt_BR', // set locale
  messages : vueMessages,
});

var appMixin = {
  el          : '#main',
  vuetify           : new Vuetify({
    icons           : {
      iconfont      : 'md', // 'mdi' || 'mdiSvg' || 'md' || 'fa' || 'fa4',
    },
    lang            : {
      current       : 'pt_BR',
      t             : (key, ...params) => i18n.t( key, params ),
    },
  }),
  mixins  : [
    jfMixin,
  ],
  data        : Object.assign( data, {
    show_menu : false,
  }),
};
