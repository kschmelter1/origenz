<?php
switch ($block['column_width']) {
  case '50':
    $col = array('col-md-6','col-md-6');
  break;
  case '60':
    $col = array('col-md-7','col-md-5');
  break;
  case '75':
    $col = array('col-md-8','col-md-4');
  break;
  default:
}
?>

<div class="block block-two-column">
  <div class="container-fluid <?php echo ($block['image_bleed'] ? 'flush-right' : '');?>">
    <div class="row">
      <div class="<?php echo $col[0];?>">
        <div class="content">
          <?php echo $block['text']; ?>
        </div>
      </div>
      <div class="<?php echo $col[1];?>">
        <?php if ($block['image_bleed']) : ?>
          <div class="img-wrap bleed">
            <?php echo echo_image($block['image']); ?>
          </div>
        <?php else : ?>
          <?php echo echo_image($block['image']); ?>
        <?php endif; ?>
      </div>
      </div>
    </div>
  </div>
</div>
