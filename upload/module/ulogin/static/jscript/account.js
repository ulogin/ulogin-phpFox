function attach(){
    if (document.forms['ulogin_fields']){
        var myForm = document.forms['ulogin_fields'];
        var expires = new Date();
        expires.setTime(expires.getTime() + (90000)); 
        var fields = '';
        
        for(var i in myForm.elements)
            if(myForm.elements[i].type=='checkbox')
                if (myForm.elements[i].checked == true)
                fields+= myForm.elements[i].value + ',';   
        fields = trim(fields, ',');
        document.cookie = "ul_import_fields = "+ fields + "; expires = " + expires.toGMTString() + "; path=/";
        if (typeof uLogin != 'undefined'){
          if (uLogin.showWindow){
            uLogin.initWidget('uLoginWindow');
            uLogin.showWindow('uLoginWindow');
          }
        }
    }
}

