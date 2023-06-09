<?php
/**
 * Team info on hover shortcode template
 */
?>
<div class="edgtf-team-holder <?php echo esc_attr($team_type); ?>">
	<div class="edgtf-team-inner">
		<?php if ( $team_image !== '' ) { ?>
			<div class="edgtf-team-image">
	            <?php echo wp_get_attachment_image($team_image,'full'); ?>

	            <?php if ($team_name !== '' || $team_position !== '') { ?>
					<div class="edgtf-team-info">
						<div class="edgtf-team-info-holder">
							<div class="edgtf-team-info-inner">
								<?php if ($team_name !== '' || $team_position !== '') {
									if ($team_name !== '') { ?>
										<<?php echo esc_attr($team_name_tag); ?> class="edgtf-team-name" <?php echo conall_edge_get_inline_style($team_name_styles); ?>><?php echo esc_attr($team_name); ?></<?php echo esc_attr($team_name_tag); ?>>
									<?php }
									if ($team_position !== "") { ?>
										<p class="edgtf-team-position" <?php echo conall_edge_get_inline_style($team_position_styles); ?>><?php echo esc_attr($team_position) ?></p>
									<?php } ?>
								<?php } ?>
                                <div class="edgtf-team-social-holder">
                                    <div class="edgtf-team-social-inner">
                                        <?php foreach( $team_social_icons as $team_social_icon ) {
                                            echo conall_edge_get_module_part($team_social_icon);
                                        } ?>
                                    </div>
                                </div>
							</div>
						</div>
					</div>	
				<?php } ?>

			</div>
		<?php } ?>
	</div>
</div>