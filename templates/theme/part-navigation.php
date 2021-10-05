<?php $logo = get_field('logo','options'); ?>

<div class="main-navigation">
  <div class="container-fluid">
    <div class="row align-items-center justify-content-between">
      <?php if ($logo) : ?>
      <div class="col-5 col-md-6 col-lg-4">
        <a href="/" class="logo">
          <img class="img-fluid" src="<?php echo $logo['url']; ?>" alt="<?php echo $logo['alt']; ?>" >
        </a>
      </div>
      <?php endif; ?>
      <div class="col-auto">
        <nav class="navbar navbar-expand navbar-light">
      	  <!--<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo01" aria-controls="navbarTogglerDemo01" aria-expanded="false" aria-label="Toggle navigation">
      	    <span class="navbar-toggler-icon"></span>
      	  </button>-->
      	  <div class="collapse navbar-collapse" id="primary-navbar">
      		<?php

      			wp_nav_menu([
      				'theme_location'    => 'primary',
      				'depth'             => 2,
      				'container'         => '',
      				'container_class'   => '',
      				'container_id'      => '',
      				'menu_class'        => 'navbar-nav mr-auto',
      				'echo'				=> true,
      				'walker'            => new bs4Navwalker()
      			]);

      		?>
      	  </div>
        	</nav>
      </div>
    </div>
  </div>
</div>
