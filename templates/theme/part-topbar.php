<div class="topbar">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-auto topbar-left">
        <div class="topbar-alert">Free Shipping on All Orders Over $25</div>
      </div>
      <div class="col-lg topbar-right">
        <div class="mycart">
          <?php get_template_part('templates/theme/part','carticon'); ?>
        </div>

        <div class="links">
            <?php global $current_user; wp_get_current_user(); ?>
            <?php echo (is_user_logged_in() ? '<a href="/my-account/" >My Account</a>' : '<a href="/my-account/" >Log In</a>');?>
            <?php get_template_part('templates/theme/part','social'); ?>
        </div>
      </div>
    </div>
  </div>
</div>
