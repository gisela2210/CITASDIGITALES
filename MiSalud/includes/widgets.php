<?php
function stat_card($icon,$label,$value,$tone='mint'):void{?><article class="stat-card"><span class="stat-icon <?=$tone?>"><?=$icon?></span><div><small><?=e($label)?></small><strong><?=e($value)?></strong></div></article><?php }
function empty_state($title,$desc,$href='',$button=''):void{?><div class="empty-state"><span>✧</span><h3><?=e($title)?></h3><p><?=e($desc)?></p><?php if($href):?><a class="btn btn-primary btn-sm" href="<?=url($href)?>"><?=e($button)?></a><?php endif;?></div><?php }
