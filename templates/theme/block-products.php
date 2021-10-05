<?php
//Get product / category info
$items = array();
switch ($block['display']) {
  case 'recent':
    $query = new WC_Product_Query( array(
      'limit' => 4,
      'orderby' => 'date',
      //'order' => 'DESC',
      'return' => 'ids',
    ) );
      $products = $query->get_products();
      foreach ($products as $cat) {
        $item = array(
          "title" => get_the_title($cat),
          "image" => get_product_img($cat),
          "link" => get_the_permalink($cat)
        );
        array_push($items, $item);
      }
  break;
  case 'random':
    $query = new WC_Product_Query( array(
      'limit' => 4,
      'orderby' => 'rand',
      //'order' => 'DESC',
      'return' => 'ids',
    ) );
      $products = $query->get_products();
      foreach ($products as $cat) {
        $item = array(
          "title" => get_the_title($cat),
          "image" => get_product_img($cat),
          "link" => get_the_permalink($cat)
        );
        array_push($items, $item);
      }
  break;
  case 'choose':
    if ($block['product_list']) :
      foreach ($block['product_list'] as $prod) {
        if ($prod['type'] == 'product') {
          $item = array(
            "title" => get_the_title($prod['product']),
            "image" => get_product_img($prod['product']),
            "link" => get_the_permalink($prod['product'])
          );
          array_push($items, $item);
        } else {
          //Get category information
          $thumbnail_id = get_woocommerce_term_meta( $prod['category'], 'thumbnail_id', true );
          $image = wp_get_attachment_url( $thumbnail_id );
          if (!$image) {$image = '/wp-content/uploads/woocommerce-placeholder.png';}
          $term = get_term_by( 'id', $prod['category'], 'product_cat' );
          $item = array(
            "title" => $term->name,
            "image" => $image,
            "link" => "/product-category/".$term->slug."/"
          );
          array_push($items, $item);
        }
      }
    endif;
  break;
  default:
        //default
}
?>
<?php if ($block['title'] || $items) : ?>
<div class="block block-products">
  <div class="container-fluid">
    <?php if ($block['title']) : ?>
    <div class="title">
      <?php echo $block['title'];?>
    </div>
    <?php endif; ?>
    <?php if ($items) : ?>
    <div class="product-blurbs row">
      <?php foreach ($items as $item) : ?>
        <div class="col-sm-6 col-lg-3">
          <div class="product-blurbs-single">
            <img src="<?php echo $item['image'];?>" alt="<?php echo $item['title'];?>">
            <div class="content">
              <h3 class="product-title"><?php echo $item['title'];?></h3>
              <a href="<?php echo $item['link'];?>" class="btn btn-primary">Start Designing</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
