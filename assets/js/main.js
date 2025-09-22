// /www/wwwroot/planwise.maxcaulfield.cn/assets/js/main.js
$(function(){
  const $form = $('#planwise-form');
  const $submit = $('#submit-btn');
  const $steps = $('#steps-list');
  const $bar = $('#progress-bar');
  const $results = $('#results-body');
  let taskId = null;
  let tickTimer = null;
  let statusTimer = null;
  let csrf = window.PLANWISE?.nextCsrf || '';

  function addStepItem(step){
    const cls = step.status;
    const li = `<li class="list-group-item ${cls}" data-step="${step.step_key}">
      <div class="d-flex justify-content-between align-items-center">
        <span>${step.step_title}</span>
        <span class="badge bg-secondary text-uppercase">${step.status}</span>
      </div>
    </li>`;
    $steps.append(li);
  }

  function updateSteps(steps){
    if ($steps.children().length === 0){
      steps.forEach(addStepItem);
    } else {
      steps.forEach(s => {
        const $li = $steps.find(`li[data-step="${s.step_key}"]`);
        $li.removeClass('pending running completed').addClass(s.status);
        $li.find('.badge').text(s.status);
      });
    }
  }

  function setProgress(p){
    p = Math.max(0, Math.min(100, parseInt(p||0,10)));
    $bar.css('width', p+'%').text(p+'%');
  }

  function appendContent(title, content){
    const html = `\n<h5>${escapeHtml(title)}</h5>\n<pre class="mb-3"><code>${escapeHtml(content)}</code></pre>`;
    $results.append(html);
  }

  function escapeHtml(str){
    return String(str||'')
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function startPolling(){
    // status polling
    statusTimer = setInterval(() => {
      if (!taskId) return;
      $.get('/api.php', { action:'get_status', task_id: taskId })
       .done(res => {
         if (!res.ok) return;
         setProgress(res.progress);
         updateSteps(res.steps||[]);
       });
    }, 2000);

    // tick executor (POST with CSRF rotation)
    tickTimer = setInterval(() => {
      if (!taskId || !csrf) return;
      $.post('/api.php', { action:'tick', task_id: taskId, csrf_token: csrf })
       .done(res => {
         if (!res.ok) return;
         csrf = res.next_csrf || csrf; // rotate CSRF from server
         if (res.content && res.step_title){
           appendContent(res.step_title, res.content);
         }
         // When completed() path triggers, API returns {completed:true}
         if (res.completed){
           clearInterval(tickTimer); tickTimer=null;
         }
       })
       .fail(() => {/* ignore transient errors */});
    }, 2200);
  }

  $form.on('submit', function(e){
    e.preventDefault();
    if ($submit.prop('disabled')) return;

    $submit.prop('disabled', true).text('正在生成...');
    $steps.empty();
    $results.empty();
    setProgress(0);

    const data = $form.serialize();
    $.post('/api.php', data)
     .done(res => {
       if (!res.ok) throw new Error(res.error||'failed');
       taskId = res.task_id;
       csrf = res.next_csrf || csrf;
       startPolling();
     })
     .fail(xhr => {
       alert('创建任务失败：' + (xhr.responseJSON?.error || '未知错误'));
       $submit.prop('disabled', false).text('生成报告');
     });
  });
});
