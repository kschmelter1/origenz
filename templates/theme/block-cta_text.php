<div class="block block-cta-text">
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-md-9 text-center">
        <?php if ($block['text']) : ?>
        <div class="content">
          <?php echo $block['text']; ?>
        </div>
        <?php endif; ?>
        <?php if ($block['button']) : ?>
        <a href="<?php echo $block['button']['url'];?>" class="btn btn-primary" <?php echo ($block['button']['target'] == "_blank" ? 'target="_blank"' : ''); ?>>
          <?php echo $block['button']['title']; ?>
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
