$(document).ready(function () {

    $(document).on('click', '#makeReport', function () {
        postData(window.location.pathname, {
            user_id: $('#user_filter').val(),
            project_id: $('#project_filter').val(),
            month: $('#month').val(),
            daily_cost: $('#daily_cost').is(':checked'),
            all_tasks: $('#all_tasks').is(':checked'),
        }, function (data){
            $(document).find('#report').html(data.view);
        })
    });

    $(document).on('change','#entity_id',function (){
        $('#title').val($("#entity_id option:selected" ).text());
    });

    $(document).on('click','#delete_cost',function (e){
        e.preventDefault();
        let url = $(this).data('url');
        let block = $(this).closest('.col');
        postData(url,{},function (data){
            if (data === false){
                alert('Произошла ошибка, попробуйте перезагрузит страницу!');
            } else {
                $(block).remove();
            }
        });
    });
});

function postData(url = '', data = {}, callback = function (data) {
    console.log(data);
}) {
    data.isAjax = true;
    var request = $.post({
        url: url,
        type: "POST",
        data: data,
        success: function (data) {
            data = JSON.parse(data);
            if (!data.success && data.code === 404) {
                alert(data.message);
            } else {
                callback(data);
            }
        }
    });
}