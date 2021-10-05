<?php

get_header();

if (!is_front_page() &&  !is_product() ) {
  get_template_part('templates/theme/part','pagetitle');
}

while ( have_posts() ) {
    the_post();
    the_content();
}
get_template_part('templates/theme/content');

get_footer();

?>
