    function ajax_form(id,url,result){
        var form = new FormData(document.getElementById(id));
        $.ajax({
            url:url,
            type:"post",
            data:form,
            processData:false,
            contentType:false,
            dataType: 'json',
            success:result,

        });

    }

    function ajax_data(data,url,result){

        $.ajax({
            url:url,
            type:"post",
            data:data,

            dataType: 'json',
            success:result,

        });

    }