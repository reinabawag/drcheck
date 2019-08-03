<nav class="navbar navbar-inverse">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?php echo site_url() ?>">AMWIRE</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li class="<?php echo uri_string() == 'main' ? 'active' : '' ?>"><a href="<?php echo site_url() ?>"><span class="glyphicon glyphicon-home"></span> Home</a></li>
        <?php
        if ($this->session->is_admin || $this->session->is_supervisor) {?>
          <li class="<?php echo uri_string() == 'main/return_item' ? 'active' : '' ?>"><a href="<?php echo site_url('main/return_item') ?>"><i class="fa fa-cubes" aria-hidden="true"></i> Return</a></li>
        <?php }
        ?>
      </ul>
      
      <ul class="nav navbar-nav navbar-right">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" id="username"><?php echo $this->session->lastName.', '.$this->session->firstName; ?>&nbsp;<span class="caret"></span></a>
          <ul class="dropdown-menu"> 
			<?php
				if (boolval($this->session->is_admin) || boolval($this->session->is_supervisor)) {?>
					<li><a href="<?php echo site_url('admin') ?>">Admin</a></li>
					<li role="separator" class="divider"></li>
				<?php
				}?>
            <li><a href="<?php echo site_url('login/getLogout'); ?>"><i class="fa fa-sign-out" aria-hidden="true"></i>&nbsp;Logout</a></li>
          </ul>
        </li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>