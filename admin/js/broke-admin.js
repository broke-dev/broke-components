window.addEventListener('load', function () {
  var brokeEditorApp = new Vue({
    el: '#broke-editor',
    data: {
      editorCode: window.brokeEditorCode,
      code: window.brokeEditorCode
    },
    mounted () {
      var vm = this;

      var langTools = ace.require('ace/ext/language_tools');

      var editor = ace.edit('broke_code_editor');
      editor.setTheme('ace/theme/monokai');
      editor.getSession().setMode('ace/mode/twig');
      editor.getSession().setTabSize(2);
      editor.setHighlightActiveLine(true);
      editor.setAutoScrollEditorIntoView(true);
      editor.setOptions({
        maxLines: Infinity,
        fontSize: '18px',
        enableBasicAutocompletion: true,
        enableLiveAutocompletion: true
      });

      editor.getSession().on('change', function(e) {
        vm.code = editor.getSession().getValue();
      });
    }
  });

  var brokeConditionsApp = new Vue({
    el: '#broke-conditions',
    data: {
      placement: window.brokeConditions.placement,
      method: window.brokeConditions.method,
      hook: window.brokeConditions.hook
    }
  });

  var brokeDataApp = new Vue({
    el: '#broke-data',
    data: {
      source: window.brokeData.source,
      query: window.brokeData.query
    },
    mounted () {
      var vm = this;

      var editor = ace.edit('broke_query_editor');
      editor.setTheme('ace/theme/monokai');
      editor.getSession().setMode('ace/mode/json');
      editor.getSession().setTabSize(2);
      editor.setHighlightActiveLine(true);
      editor.setAutoScrollEditorIntoView(true);
      editor.setOptions({
        maxLines: Infinity,
        fontSize: '18px'
      });

      editor.getSession().on('change', function(e) {
        vm.query = editor.getSession().getValue();
      });
    }
  });
});
