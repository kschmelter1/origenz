<?php $flags = get_field( 'flags', 'options' ); ?>
<div class="block block-team">
  <div class="container-fluid">
        <?php foreach($block['team'] as $team) : ?>
          <div class="row single-team">
            <?php if ($team['image']) : ?>
            <div class="col-md-6">
              <div class="profile">
                <?php if ($team['countries']) : ?>
                <div class="countries">
                  <?php foreach ($team['countries'] as $country) : ?>
                    <div class="country">
                      <div class="country-flag">
                         <?php
                            foreach ($flags as $flag) {
                              if ($flag['country'] == $country['country']) {
                                echo '<img src="/wp-content/themes/bootstrap-4/flags/'.$flag['file_name'].'" class="img-fluid">';
                              }
                            }
                         ?>
                       </div>
                      <div class="country-percent"><?php echo $country['percent'].'%';?></div>
                      <div class="country-name"><?php echo $country['country']->post_title; ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="img-wrap">
                  <?php echo echo_image($team['image']); ?>
                </div>
              </div>
             </div>
           <?php endif; ?>
            <div class="col-md">
              <h2 class="title"><?php echo $team['name']; ?></h2>
              <div class="subtitle"><?php echo $team['title']; ?></div>
              <div class="bio"><?php echo $team['bio'];?></div>
            </div>
          </div>
        <?php endforeach; ?>
    </div>
  </div>
</div>
