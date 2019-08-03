<div class="row">
    <div class="col-md-4 col-md-offset-4" style="margin-top: 5%">
        <div class="panel panel-primary">
            <div class="panel-heading"><span class="glyphicon glyphicon-user"></span>&nbsp;DRCHECK LOGIN</div>
            <div class="panel-body">
                <form id="form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                    </div>
                    <button class="btn btn-primary btn-block">Login</button>
                    <div class="loader" align="center" style="margin-top: 10px; display: none;">
                        <img src="<?php echo base_url('assets/images/spin.gif') ?>" width="20px">
                        loading please wait
                    </div>
                    <p id="msg" align="center" style="margin-top: 10px"></p>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
        $('form#form').on('submit', function(e){
            e.preventDefault();

            $('#msg').html('');
            $('div.loader').show();

            $.ajax({
                url: '<?php echo site_url('login/get_login_new') ?>',
                type: 'POST',
                data: $('form#form').serialize(),
                dataType: 'json'
            }).done(function(data){
                $('div.loader').fadeOut(function() {
                    if (data.status) {
                        $('#msg').html(data.message);
                        console.log('true');
                        $(location).attr('href', '<?php echo site_url('main'); ?>');
                    } else {
                        $('#msg').html(data.message);
                        console.log('false');
                    }
                });                
            }).fail(function(){
                $('div.loader').fadeOut();
                console.log('Error');
            })
        })
    })
</script>
