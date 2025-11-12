function getDebugContent(date){
    $.ajax({
        url: debug_url,
        type: 'GET',
        dataType: 'json',
        data: {
            ajax: 1,
            method: 'getFile',
            debug: date
        },
        success: function(result){
            console.log(result);
            if(result.success){
                $('#DEBUG-FIELD').val(result.debug);
            }
        }
    });
}

$(document).on('change', '#DATE-SELECTOR', function(){
    getDebugContent($('#DATE-SELECTOR option:selected').val());
});

$(document).on('click', '#REFRESH-DATE', function(){
    getDebugContent($('#DATE-SELECTOR option:selected').val());
});

$(document).on('click', '#DELETE-DATE', function(){
    if(confirm('Delete debug on date '+ $('#DATE-SELECTOR option:selected').val() + '?')){
        console.log('delete');
        $.ajax({
            url: debug_url,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                method: 'removeFile',
                debug: $('#DATE-SELECTOR option:selected').val()
            },
            success: function(result){
                window.location.reload();
            }
        })
    }
})

$(document).on('click', '#DELETE-ALL', function(){
    if(confirm('Clear all debug files?')){
        console.log('delete');
        $.ajax({
            url: debug_url,
            type: 'POST',
            dataType: 'json',
            data: {
                ajax: 1,
                method: 'removeAll',
            },
            success: function(result){
                window.location.reload();
            }
        })
    }
})

$(document).ready(function(){
    var selected_date = $('#DATE-SELECTOR option:selected').val();
    if(selected_date !== undefined && selected_date != ''){
        getDebugContent(selected_date);
    }
});