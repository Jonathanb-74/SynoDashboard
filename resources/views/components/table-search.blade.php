@props(['total'])
<div class="d-flex gap-2 align-items-center border-bottom py-2 px-3">
    <div class="input-group input-group-sm" style="max-width:240px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control" placeholder="Rechercher…"
               x-model="search" @input="_render()">
        <button type="button" class="btn btn-outline-secondary" x-show="search"
                @click="search=''; _render()">
            <i class="bi bi-x"></i>
        </button>
    </div>
    <span class="text-muted small ms-auto"
          x-text="_visibleCount + ' / ' + _totalCount + ' entrées'"></span>
</div>
