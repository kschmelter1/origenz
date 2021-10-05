<div class="block block-cta-large">
  <div class="container-fluid">
    <div class="row align-items-center justify-content-center" style="background-image:url('<?php echo $block['background']['url'];?>')">
      <?php if ($block['image']) : ?>
      <div class="col-md-6">
        <img class="img-fluid" src="<?php echo $block['image']['url'];?>" alt="<?php echo $block['image']['alt'];?>">
      </div>
      <?php endif; ?>
      <div class="col-md-6">
        <div class="content">
          <?php echo $block['text']; ?>
        </div>
      </div>
    </div>
  </div>
</div>
