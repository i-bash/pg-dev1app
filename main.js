var lib={}

$(()=>{
	
	lib.clearSql=()=>{
		$('#sql').empty().hide();
	};
	
	lib.server=(action,params,callback)=>{
		$('#error,#success').hide();
		$('#loader').show();
		$.ajax({
			method:'post',
			url:'server.php?action='+action,
			data:params,
			success:(res)=>{
				let separator='<br/>---<br/>';
				if(res.sql.length){
					let html=$('#sql').html();
					$('#sql')
						.fadeOut(
							'slow',
							()=>{
								$('#sql')
								.html(
									res.sql.map(
										s=>{
											return s.trimLeft().replace(/\n/g,'<br/>').replace(/\t/g,'&nbsp;');
										}
									)
									.reverse()
									.join(separator)
									+(html&&res.sql?separator:'')
									+html
								)
								.fadeIn('slow')
							}
						)
					;
				}
				if(res.err===null){
					if(callback!==undefined){
						callback(res.data);
					}
					//$('#success').slideDown('slow');
				}
				else{
					$('#error').html(
						res.err.message.replace(/\n\s*\^/,'').replace(/\n/g,'<br/>')
					).slideDown('slow');
				}
				$('#loader').fadeOut('slow');
			},
			error:(e)=>{
				alert('Unexpected server error');
				console.error(e);
			}
		});
	}

	lib.displayPage=page=>{
		$.ajax('pages/'+page+'.html')
			.then(
				html=>{
					$('#page').hide().html(html).fadeIn().removeClass().addClass(page);
					//return $.ajax('pages/'+page+'.js');
				},
				()=>{$('#page').html('Page "'+page+'" not found');}
			)
/*
			.then(
				js=>{
					$('#page-js').html(js);
				},
				()=>{console.warn(page+'.js not found');}
			)
*/
		;
		
	}
	
	//turn form into ajax
	lib.ajaxForm=(selector,callback,extraPars=[])=>{
		$(document).off('submit',selector);
		$(document).on(
			'submit',
			selector,
			function(e){
				e.preventDefault();
				let form=this;
				lib.server(
					$(form).attr('action'),
					$(form).serialize(),
					callback===undefined
						?()=>{console.info('ajax form ok');}
						:callback
				);
			}
		);
	}

	//display alert
	lib.alert=(message,style='info')=>{
		$('<div/>',{class:'alert alert-'+style+' fade'}).html(message).appendTo('#alert').addClass('in').delay(2000).slideUp('slow',function(){$(this).remove();});
	}
});

