<?php

$design_choice = origenz()->get_design_choice();

?>

<div class="design-choice">
    <img src="<?php echo $design_choice['image']; ?>">
    <span><?php echo $design_choice['name']; ?></span>
    <a href="/designer/?step=1">Re-design Graphic</a>
</div>
