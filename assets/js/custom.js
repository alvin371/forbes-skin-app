load_ucapan();
function load_ucapan(){
    $('#div_ucapan').html('<p>Memuat data ucapan & doa...</p>');
    $.ajax({
        url:'<?=base_url()?>p/ucapan/<?=$this->template->encode($data['id'])?>',
        success:function(html){
            // $('#div_ucapan').html(html);
        }
    }); 
}


$("#form-rsvp").submit(function() {
    var form = $(this);
        var mydata = new FormData(this);
        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(".btn-rsvp").addClass("disabled").html('Proses...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                var str = response;
        console.log(str);
                if(str.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $('#ucapan').val('');
                    load_ucapan();
                    $(".btn-rsvp").removeClass("disabled").html('<i class="mdi mdi-send"></i> Kirim').attr('disabled', false);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-rsvp").removeClass("disabled").html('<i class="mdi mdi-send"></i> Kirim').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-rsvp").removeClass("disabled").html('<i class="mdi mdi-send"></i> Kirim').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });


$("#form-ucapan").submit(function() {
    var form = $(this);
        var mydata = new FormData(this);
        $.ajax({
            type: "POST",
            url: form.attr("action"),
            data: mydata,
            cache: false,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(".btn-send").addClass("disabled").html('Proses...').attr('disabled', true);
                form.find(".form-message").slideUp().html("");
            },
            success: function(response, textStatus, xhr) {
                var str = response;
        console.log(str);
                if(str.indexOf("success") != -1) {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $('#ucapan').val('');
                    load_ucapan();
                    $(".btn-send").removeClass("disabled").html('<i class="mdi mdi-send"></i> Kirim').attr('disabled', false);
                } else {
                    $(".form-message").hide().html(response).slideDown("fast");
                    $(".btn-send").removeClass("disabled").html('<i class="mdi mdi-send"></i> Kirim').attr('disabled', false);
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $(".btn-send").removeClass("disabled").html('<i class="mdi mdi-send"></i> Kirim').attr('disabled', false);
                $(".form-message").hide().html(xhr).slideDown("fast");
            }
        });
        return false;
    });