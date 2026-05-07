/**
 * state.js
 * Simple global state management for Vanilla JS
 */

const State = {
  _data: {},
  _listeners: {},

  get(key) {
    return this._data[key];
  },

  set(key, value) {
    this._data[key] = value;
    this.notify(key, value);
  },

  subscribe(key, callback) {
    if (!this._listeners[key]) {
      this._listeners[key] = [];
    }
    this._listeners[key].push(callback);
    // Return unsubscribe function
    return () => {
      this._listeners[key] = this._listeners[key].filter(cb => cb !== callback);
    };
  },

  notify(key, value) {
    if (this._listeners[key]) {
      this._listeners[key].forEach(callback => callback(value));
    }
  }
};

export default State;
