<div class="block block-hero">
  <div class="swiper-container">

      <div class="swiper-wrapper">
          <?php foreach ($block['slides'] as $slide) : ?>
            <div class="swiper-slide" <?php if ($slide['background']) : ?>style="background-image:url('<?php echo $slide['background']['url'];?>')"<?php endif; ?>>
              <div class="container-fluid"><div class="row align-items-center justify-content-center">
                <?php if ($slide['image']) : ?>
                  <div class="col-md-auto image">
                    <img class="img-fluid" src="<?php echo $slide['image']['url'];?>" alt="<?php echo $slide['image']['alt'];?>">
                  </div>
                <?php endif; ?>
                <div class="col-lg-5 content">
                  <?php if ($slide['title']) : ?><h1><?php echo $slide['title']; ?></h1><?php endif; ?>
                  <div class="text"><?php echo $slide['text']; ?></div>
                  <?php if ($slide['button']) : ?>
                    <a class="btn btn-primary" href="<?php echo $slide['button']['url'];?>"><?php echo $slide['button']['title']; ?></a>
                  <?php endif; ?>
                </div>
              </div></div>
            </div>
          <?php endforeach; ?>
      </div>

      <?php if (count($block['slides']) > 1) : ?>
        <div class="swiper-pagination-wrapper container-fluid">
            <div class="swiper-pagination"></div>
        </div>
      <?php endif; ?>
  </div>
</div>
