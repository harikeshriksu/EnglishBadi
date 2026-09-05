<?php
/**
 * Renders the custom WYSIWYG editor used by the Lessons and Pages admin
 * forms: toolbar + contenteditable area + a hidden textarea (named
 * $name) that editor.js keeps in sync, which is what actually gets
 * submitted with the form.
 *
 * $html is the existing sanitized body HTML when editing an item that
 * already exists, or '' for a brand new one.
 */
function render_editor(string $name, string $html): void
{
    $uploadUrl = base_url('/admin/upload-image.php');
    $csrf = csrf_token();
    ?>
    <div class="editor-instance" data-editor-root data-upload-url="<?php echo e($uploadUrl); ?>" data-csrf-token="<?php echo e($csrf); ?>">
      <div class="editor-toolbar" data-editor-toolbar>
        <div class="editor-toolbar__group">
          <button type="button" class="editor-btn" data-cmd="bold" title="Bold"><?php echo icon('bold'); ?></button>
          <button type="button" class="editor-btn" data-cmd="italic" title="Italic"><?php echo icon('italic'); ?></button>
          <button type="button" class="editor-btn" data-cmd="underline" title="Underline"><?php echo icon('underline'); ?></button>
          <button type="button" class="editor-btn" data-cmd="strikeThrough" title="Strikethrough"><?php echo icon('strikethrough'); ?></button>
        </div>
        <div class="editor-toolbar__group">
          <button type="button" class="editor-btn editor-btn--wide" data-cmd="formatBlock" data-value="H2" title="Heading 2">H2</button>
          <button type="button" class="editor-btn editor-btn--wide" data-cmd="formatBlock" data-value="H3" title="Heading 3">H3</button>
          <button type="button" class="editor-btn" data-cmd="formatBlock" data-value="P" title="Normal paragraph"><?php echo icon('paragraph'); ?></button>
        </div>
        <div class="editor-toolbar__group">
          <button type="button" class="editor-btn" data-cmd="insertUnorderedList" title="Bulleted list"><?php echo icon('list-bullet'); ?></button>
          <button type="button" class="editor-btn" data-cmd="insertOrderedList" title="Numbered list"><?php echo icon('list-number'); ?></button>
          <button type="button" class="editor-btn" data-cmd="formatBlock" data-value="BLOCKQUOTE" title="Blockquote"><?php echo icon('quote'); ?></button>
        </div>
        <div class="editor-toolbar__group" style="position:relative;">
          <button type="button" class="editor-btn" data-action="text-color" title="Text colour"><?php echo icon('text-color'); ?></button>
          <div class="editor-color-popover" data-color-popover data-color-mode="foreColor">
            <div class="editor-color-grid" data-color-grid></div>
            <div class="editor-hex-row">
              <input type="text" placeholder="#RRGGBB" data-hex-input>
              <button type="button" data-hex-apply>Apply</button>
            </div>
          </div>
        </div>
        <div class="editor-toolbar__group" style="position:relative;">
          <button type="button" class="editor-btn" data-action="highlight-color" title="Highlight colour"><?php echo icon('highlight'); ?></button>
          <div class="editor-color-popover" data-color-popover data-color-mode="hiliteColor">
            <div class="editor-color-grid" data-color-grid></div>
            <div class="editor-hex-row">
              <input type="text" placeholder="#RRGGBB" data-hex-input>
              <button type="button" data-hex-apply>Apply</button>
            </div>
          </div>
        </div>
        <div class="editor-toolbar__group">
          <button type="button" class="editor-btn" data-action="link" title="Insert link"><?php echo icon('link'); ?></button>
          <button type="button" class="editor-btn" data-cmd="unlink" title="Remove link"><?php echo icon('unlink'); ?></button>
          <button type="button" class="editor-btn" data-action="image" title="Insert image"><?php echo icon('image'); ?></button>
        </div>
        <div class="editor-toolbar__group">
          <button type="button" class="editor-btn" data-cmd="justifyLeft" title="Align left"><?php echo icon('align-left'); ?></button>
          <button type="button" class="editor-btn" data-cmd="justifyCenter" title="Align centre"><?php echo icon('align-center'); ?></button>
        </div>
        <div class="editor-toolbar__group">
          <button type="button" class="editor-btn" data-cmd="removeFormat" title="Clear formatting"><?php echo icon('clear-format'); ?></button>
          <button type="button" class="editor-btn" data-action="undo" title="Undo"><?php echo icon('undo'); ?></button>
          <button type="button" class="editor-btn" data-action="redo" title="Redo"><?php echo icon('redo'); ?></button>
        </div>
        <div class="editor-toolbar__group">
          <button type="button" class="editor-btn editor-btn--wide" data-action="toggle-telugu" title="Switch the editor's font between English and Telugu for comfort">Aa/&#3077;</button>
          <label class="editor-btn editor-btn--wide" style="gap:6px;" title="When ticked, pasting keeps the original formatting instead of plain text">
            <input type="checkbox" data-paste-mode style="width:15px;height:15px;"> Paste format
          </label>
          <button type="button" class="editor-btn editor-btn--wide" data-action="toggle-preview" title="Preview how this will look on the live site">Preview</button>
        </div>
      </div>
      <div class="editor-wrap">
        <div class="editor-body content-body" contenteditable="true" data-editor-body data-placeholder="Start writing here..."><?php echo $html; ?></div>
        <div class="editor-preview content-body" data-editor-preview></div>
      </div>
      <textarea name="<?php echo e($name); ?>" data-editor-output class="visually-hidden"><?php echo e($html); ?></textarea>
    </div>
    <?php
}
