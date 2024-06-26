tinymce.init({
  selector: '.mceClass',
  height: '40',
  theme: 'modern',
  language: "it",
  fontsize_formats: "6pt 8pt 10pt 12pt 14pt 16pt 18pt 20pt 24pt 30pt 36pt",
  plugins: [
    'advlist autolink lists link image charmap print preview hr anchor pagebreak',
    'searchreplace wordcount visualblocks visualchars code fullscreen',
    'insertdatetime media nonbreaking save table contextmenu directionality',
    'emoticons template textcolor colorpicker textpattern paste '/*imagetools*/
  ],
  toolbar1: 'insertfile undo redo | fontsizeselect | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | print preview media | forecolor backcolor',
  image_advtab: true,
  /*templates: [
    { title: 'Test template 1', content: 'Test 1' },
    { title: 'Test template 2', content: 'Test 2' }
  ],*//*
  imagetools_cors_hosts: ['www.tinymce.com', 'codepen.io'],
  content_css: '//www.tinymce.com/css/codepen.min.css'*/
 });
