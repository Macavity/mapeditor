<script setup lang="ts">
  import { RouterLink, RouterView } from 'vue-router';
</script>

<template>
  <div class="container">
    <header class="navbar navbar-expand-lg navbar-light bg-light">
      <div class="container-fluid">
        <a class="navbar-brand" href="#">TileStove</a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent"
          aria-controls="navbarSupportedContent"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <RouterLink to="/" class="nav-link" active-class="active"
                >News</RouterLink
              >
            </li>
            <li class="nav-item">
              <RouterLink
                to="/manage-maps"
                class="nav-link"
                active-class="active"
                >Maps</RouterLink
              >
            </li>
            <li class="nav-item">
              <RouterLink
                to="/manage-tilesets"
                class="nav-link"
                active-class="active"
                >Tilesets</RouterLink
              >
            </li>
            <li class="nav-item">
              <RouterLink
                to="/test-drive"
                class="nav-link"
                active-class="active"
                >Testdrive</RouterLink
              >
            </li>
          </ul>
          <form class="d-flex">
            <input
              class="form-control me-2"
              type="search"
              placeholder="Search"
              aria-label="Search"
            />
            <button class="btn btn-outline-success" type="submit">
              Search
            </button>
          </form>
        </div>
      </div>
    </header>
    <RouterView />
  </div>
</template>

<style>
  #app {
    /* max-width: 1280px; */
    margin: 0 auto;
  }
</style>
