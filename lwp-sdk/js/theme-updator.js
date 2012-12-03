jQuery(document).ready(function($){
	if($('#lwp-theme-updater').length > 0){
		var updateProgressBar = function(current, total){
			$('#updater-status .ui-progressbar-value').animate({
				width: (Math.floor(current / total * 100) + '%')
			});
		}
		
		var addMessage = function(msg){
			$('#updater-status ol').append('<li class="loading"><i></i>' + msg + '</li>');
		}
		
		var makeSuccess = function(){
			$('#updater-status ol li:last-child').removeClass('loading').addClass('success').append('<strong>...OK<strong>').effect('highlight');
		}
		
		var errorOccured = function(msg){
			$('#updater-status ol li:last-child').removeClass('loading').addClass('error').append('<strong>...' + msg + '<strong>').effect('highlight');
		}
		
		$('#lwp-theme-updater form').submit(function(e){
			e.preventDefault();
			if(!confirm(LWPUpdater.labelConfirm)){
				return;
			}else{
				$(this).find('input[type=submit]').attr('disabled', true).addClass('disabled');
				var endPoint = $(this).attr('action');
				var connectionType = $(this).find('input[name=connection_type]').length > 1
					? $(this).find('input[name=connection_type]:checked').val()
					: $(this).find('input[name=connection_type]').val();
				var vars = {
					_wpnonce: LWPUpdater.nonce,
					action: LWPUpdater.aciton,
					connection_type: connectionType,
					hostname: $(this).find('input[name=hostname]').val(),
					username: $(this).find('input[name=username]').val(),
					password: $(this).find('input[name=password]').val(),
					public_key: $(this).find('input[name=public_key]').val(),
					private_key: $(this).find('input[name=private_key]').val(),
					currentStep: 0,
					totalStep: 4
				};
				//Show form
				$('#updater-status').fadeIn();
				var updator = function(result){
					if(result.success){
						makeSuccess();
						vars.currentStep++;
						updateProgressBar(vars.currentStep, vars.totalStep);
						addMessage(result.message);
						if(result.zip_name){
							vars.zip_name = result.zip_name;
						}
						if(vars.currentStep < vars.totalStep){
							$.post(endPoint, vars, updator);
						}else{
							makeSuccess();
							setTimeout(function(){
								window.location.reload();
							}, 3000);
						}
					}else{
						errorOccured(result.message);
						$('#lwp-theme-updater input[type=submit]').attr('disabled', false).removeClass('disabled');
					}
				}
				$('#updater-status ol li').remove();
				$('#updater-status .ui-progressbar-value').css('width', 0);
				addMessage(LWPUpdater.labelStart);
				$.post(endPoint, vars, updator);
			}
		});
	}
});