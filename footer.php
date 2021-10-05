<?php get_template_part('templates/theme/part','newsletter');?>
<?php $logo = get_field('logo','options');?>

<footer>
  <div class="container-fluid">
    <div class="row justify-content-between">

      <div class="col-md-3">
        <?php if ($logo) : ?>
          <a class="logo" href="/"><img class="img-fluid" src="<?php echo $logo['url'];?>" alt="<?php echo $logo['alt'];?>"></a>
        <?php endif; ?>
      </div>

      <div class="col-md-auto">
        <?php

          wp_nav_menu([
            'theme_location'    => 'footer1',
            'depth'             => 2,
            'container'         => '',
            'container_class'   => '',
            'container_id'      => '',
            'menu_class'        => 'nav',
            'echo'				=> true,
            'walker'            => new bs4Navwalker()
          ]);

        ?>

        <?php

          wp_nav_menu([
            'theme_location'    => 'footer2',
            'depth'             => 2,
            'container'         => '',
            'container_class'   => '',
            'container_id'      => '',
            'menu_class'        => 'nav',
            'echo'				=> true,
            'walker'            => new bs4Navwalker()
          ]);

        ?>
      </div>

    </div>

    <div class="row justify-content-between bottom-bar">
      <div class="col-md-6 footer-credit">
          <span class="copy">&copy; <?php echo date('Y'); ?> <a href="<?php echo get_bloginfo('url');?>"><?php echo get_bloginfo('name');?></a></span> <span class="divider">&bull;</span> <span class="credit">Designed by <a href="https://compulse.com/" target="_blank">Compulse Integrated Marketing</a></span>
          <?php if (get_field('locality_footer_text','options')) : echo '<div class="seo">'.get_field('locality_footer_text','options').'</div>'; endif;?>
      </div>

      <div class="col-md-auto footer-links">
        <?php

          wp_nav_menu([
            'theme_location'    => 'footer3',
            'depth'             => 2,
            'container'         => '',
            'container_class'   => '',
            'container_id'      => '',
            'menu_class'        => 'nav',
            'echo'				=> true,
            'walker'            => new bs4Navwalker()
          ]);

        ?>
      </div>
    </div>
  </div>

</footer>
<script src="https://cdnjs.﻿cloudflare.com/ajax/libs/gsap/2.1.3/TweenMax.min.js"></script>﻿
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/2.1.3/TimelineMax.min.js"></script>

<?php wp_footer(); ?>
</body>

</html>
