export const name = 'helper';

/**
 * Runs the given callback when document is ready.
 * @param {function} callback
 */
export function documentReady(callback) {
    if (document.readyState === 'interactive' || document.readyState === 'complete' ) {
        callback();
    }
    else {
        document.addEventListener('DOMContentLoaded', callback);
    }
};
