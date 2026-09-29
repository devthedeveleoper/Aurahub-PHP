<?php page_header('Upload · Aurahub'); ?>
<form id="upform" class="card" method="post" enctype="multipart/form-data"
  data-upload-url="<?= e(WRAPPER_BASE_URL . WRAPPER_PATH_UPLOAD_URL) ?>"
  data-remote-add-url="<?= e(WRAPPER_BASE_URL . WRAPPER_PATH_REMOTE_ADD) ?>"
  data-upload-folder="<?= e(WRAPPER_FOLDER_ID) ?>"
  data-upload-field="<?= e(WRAPPER_UPLOAD_FIELD) ?>">
  <h1>Add a video</h1>
  <p id="err" class="error" hidden></p>
  <?= csrf_field() ?>
  <div class="tabs" role="radiogroup" aria-label="Video source">
    <label><input type="radio" name="mode" value="file"><span>Upload a file</span></label>
    <label><input type="radio" name="mode" value="remote" checked><span>From a link</span></label>
  </div>
  <label>Title <input name="title" required maxlength="150"></label>
  <label>Description <textarea name="description" rows="4"></textarea></label>
  <label id="f-file">Video file <input type="file" name="video" accept="video/*" required data-max-bytes="<?= MAX_VIDEO_MB * 1048576 ?>"></label>
  <label id="f-remote" hidden>Direct video URL
    <input type="url" name="remote_url" placeholder="https://example.com/video.mp4">
    <span class="muted">The video is imported in the background. Your browser doesn't upload it.</span>
  </label>
  <label>Thumbnail <span class="muted">(optional)</span> <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp,image/gif"></label>
  <progress id="bar" value="0" max="100" hidden></progress>
  <p id="status" class="muted" hidden></p>
  <button id="go" class="btn" type="submit">Publish</button>
</form>
<script>
const form = document.getElementById('upform'), bar = document.getElementById('bar'),
      status = document.getElementById('status'), err = document.getElementById('err'), go = document.getElementById('go'),
      fFile = document.getElementById('f-file'), fRemote = document.getElementById('f-remote');
const mode = () => form.mode.value;
function syncMode() {
  const remote = mode() === 'remote';
  fFile.hidden = remote; fRemote.hidden = !remote;
  form.video.required = !remote; form.remote_url.required = remote;
}
form.querySelectorAll('input[name=mode]').forEach(r => r.addEventListener('change', syncMode));
syncMode();

form.addEventListener('submit', (ev) => {
  ev.preventDefault();
  err.hidden = true; go.disabled = true; status.hidden = false; bar.value = 0;
  const remote = mode() === 'remote';
  bar.hidden = remote;
  status.textContent = remote ? 'Sending link…' : 'Starting upload…';
  const fail = (m) => { err.textContent = m; err.hidden = false; go.disabled = false; bar.hidden = true; status.hidden = true; };
  const request = (data, onload, onprogress) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', location.href);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    if (onprogress) xhr.upload.onprogress = onprogress;
    xhr.onerror = () => fail('Network error. Check your connection and try again.');
    xhr.onload = () => {
      let response;
      try { response = JSON.parse(xhr.responseText); }
      catch { return fail('Unexpected server response (HTTP ' + xhr.status + ').'); }
      onload(response);
    };
    xhr.send(data);
  };
  if (remote) {
    const remoteUrl = new URL(form.dataset.remoteAddUrl);
    remoteUrl.searchParams.set('url', form.elements.namedItem('remote_url').value.trim());
    if (form.dataset.uploadFolder) remoteUrl.searchParams.set('folder', form.dataset.uploadFolder);
    fetch(remoteUrl, {headers: {Accept: 'application/json'}, cache: 'no-store'})
      .then((response) => {
        if (!response.ok) throw new Error('The video service rejected the import (HTTP ' + response.status + ').');
        return response.json();
      })
      .then((result) => {
        if (result.success === false) throw new Error(result.message || result.msg || 'The video service rejected the import.');
        const data = result.result || result;
        const remoteId = data.id || data.remote_id || data.remoteId;
        if (!remoteId) throw new Error('The video service returned no import ID.');
        const finish = new FormData();
        finish.append('csrf', form.querySelector('[name=csrf]').value);
        finish.append('action', 'complete_remote_import');
        finish.append('title', form.elements.namedItem('title').value);
        finish.append('description', form.elements.namedItem('description').value);
        finish.append('remote_id', remoteId);
        const thumbnail = form.elements.namedItem('thumbnail').files[0];
        if (thumbnail) finish.append('thumbnail', thumbnail);
        request(finish, (response) => response.ok ? location.assign(response.redirect) : fail(response.error));
      })
      .catch((error) => fail(error.message || 'Could not start the remote import.'));
    return;
  }

  const video = form.video.files[0];
  if (!video) return fail('Choose a video file.');
  if (video.size > Number(form.video.dataset.maxBytes)) return fail('Video is larger than <?= MAX_VIDEO_MB ?> MB.');

  const uploadUrl = new URL(form.dataset.uploadUrl);
  if (form.dataset.uploadFolder) uploadUrl.searchParams.set('folder', form.dataset.uploadFolder);
  fetch(uploadUrl, {headers: {Accept: 'application/json'}, cache: 'no-store'})
    .then((response) => {
      if (!response.ok) throw new Error('The upload service could not issue a URL (HTTP ' + response.status + ').');
      return response.json();
    })
    .then((issued) => {
      if (!issued.url) throw new Error('The upload service returned no temporary URL.');
    status.textContent = 'Uploading directly to the video host…';
    const payload = new FormData();
    payload.append(form.dataset.uploadField, video, video.name);
    const direct = new XMLHttpRequest();
    direct.open('POST', issued.url);
    direct.upload.onprogress = (progress) => {
      if (!progress.lengthComputable) return;
      const pct = Math.round(progress.loaded / progress.total * 100);
      bar.value = pct;
      status.textContent = 'Uploading directly to the video host… ' + pct + '%';
    };
    direct.onerror = () => fail('Direct upload failed. The temporary upload URL may have expired; try again.');
    direct.onload = () => {
      if (direct.status < 200 || direct.status >= 300) return fail('Video host rejected the upload (HTTP ' + direct.status + ').');
      const finish = new FormData();
      finish.append('csrf', form.querySelector('[name=csrf]').value);
      finish.append('action', 'complete_direct_upload');
      finish.append('title', form.elements.namedItem('title').value);
      finish.append('description', form.elements.namedItem('description').value);
      finish.append('upload_response', direct.responseText);
      const thumbnail = form.elements.namedItem('thumbnail').files[0];
      if (thumbnail) finish.append('thumbnail', thumbnail);
      status.textContent = 'Saving video…';
      request(finish, (response) => response.ok ? location.assign(response.redirect) : fail(response.error));
    };
    direct.send(payload);
    })
    .catch((error) => fail(error.message || 'Could not get an upload URL from the video service.'));
});
</script>
<?php page_footer(); ?>
