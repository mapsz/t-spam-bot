<template>
<div>

  <!-- Single -->
  <div v-if="data.length < 2">
    {{data[0].name}}
  </div>

  <!-- Multi -->
  <div v-else>
    <!-- List -->
    <span>
      {{data.length}}
    </span>

    <!-- Button trigger modal -->
    <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#juge-list-list-modal">
      Список
    </button>
    
    <!-- Modal -->
    <div class="modal fade" id="juge-list-list-modal" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
      <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">sm</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
          </div>
          <div class="modal-body">
            Body
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary">Save</button>
          </div>
        </div>
      </div>
    </div>
  </div>


</div>
</template>

<script>
export default {
props: ['data'],
}
</script>

<style>

</style>