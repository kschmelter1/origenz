<?php wc_print_notices(); ?>

<div class="design-wrapper" id="design-app">
  <div class="container-fluid">
    <div class="design-nav row">
        <div class="col-md-12">
            <ul class="nav nav-pills nav-fill">
                <li class="nav-item">
                    <a href="#" @click.prevent="goToStep(1)" :class="{'nav-link':true,'active':step==1}">step one: input your ethnicity</a>
                </li>
                <li class="nav-item">
                    <a href="#" @click.prevent="goToStep(2)" :class="{'nav-link':true,'active':step==2,'disabled':step<2}">step two: choose a design</a>
                </li>
                <li class="nav-item">
                    <a href="#" @click.prevent="goToStep(3)" :class="{'nav-link':true,'active':step==3,'disabled':step<3}">step three: choose a product</a>
                </li>
            </ul>
        </div>
    </div>
  </div>

    <div class="design-content">

            <?php

            get_template_part( 'templates/design/input-ethnicity' );
            get_template_part( 'templates/design/choose-design' );
            get_template_part( 'templates/design/choose-product' );

            ?>

    </div>
</div>
