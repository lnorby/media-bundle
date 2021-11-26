import axios from 'axios';
import Sortable from 'sortablejs';

const _form = document.querySelector('.js-form');

function increaseDataAttribute(_element, attribute) {
    attribute = `data-${attribute}`;

    if (!_element.hasAttribute(attribute)) {
        _element.setAttribute(attribute, 1);
    } else {
        _element.setAttribute(attribute, parseInt(_element.getAttribute(attribute)) + 1);
    }
}

function decreaseDataAttribute(_element, attribute) {
    attribute = `data-${attribute}`;

    if (!_element.hasAttribute(attribute)) {
        _element.setAttribute(attribute, -1);
    } else {
        _element.setAttribute(attribute, parseInt(_element.getAttribute(attribute)) - 1);
    }
}

function uploadImage(_uploader, file) {
    const _image = _uploader.querySelector('template').content.firstElementChild.cloneNode(true);
    _image.innerHTML = _image.innerHTML.replaceAll('__name__', _uploader.getAttribute('data-index'));
    _image.classList.add('is-uploading');
    _uploader.appendChild(_image);

    updateImagePositions(_uploader);

    increaseDataAttribute(_form, 'uploads');
    increaseDataAttribute(_uploader, 'index');
    decreaseDataAttribute(_uploader, 'limit');

    const formData = new FormData();
    formData.append('image', file, file.name);
    formData.append('min_height', _uploader.getAttribute('data-min-height'));
    formData.append('min_width', _uploader.getAttribute('data-min-width'));

    axios
        .post(`/_media/upload-image`, formData)
        .then((response) => {
            _image.classList.remove('is-uploading');
            _image.style.backgroundImage = `url('${response.data.url}')`;
            _image.querySelector('.js-uploaded-image-media-id').value = response.data.id;
        })
        .catch((error) => {
            removeImage(_uploader, _image);
            alert(error.response.data);
        })
        .then(() => {
            decreaseDataAttribute(_form, 'uploads');
        });
}

function removeImage(_uploader, _image) {
    _image.parentNode.removeChild(_image);
    increaseDataAttribute(_uploader, 'limit');

    updateImagePositions(_uploader);
}

function updateImagePositions(_uploader) {
    let position = 1;

    _uploader.querySelectorAll('.js-uploaded-image-position').forEach((_positionInput) => {
        _positionInput.value = position++;
    });
}

function uploadFile(_uploader, file) {
    _uploader.classList.add('is-uploading');

    increaseDataAttribute(_form, 'uploads');

    const formData = new FormData();
    formData.append('file', file, file.name);

    axios
        .post(`/_media/upload-file`, formData)
        .then((response) => {
            _uploader.classList.add('is-uploaded');
            _uploader.querySelector('.js-file-uploader-media-id').value = response.data.id;
        })
        .catch((error) => {
            alert(error.response.data);
        })
        .then(() => {
            _uploader.classList.remove('is-uploading');
            decreaseDataAttribute(_form, 'uploads');
        });
}

document.addEventListener('change', (event) => {
    if (event.target.classList.contains('js-image-uploader-file-picker')) {
        const _filePicker = event.target;
        const _uploader = _filePicker.closest('.js-image-uploader');

        for (let i = 0; i < _filePicker.files.length; i += 1) {
            if (0 === parseInt(_uploader.getAttribute('data-limit'))) {
                break;
            }

            uploadImage(_uploader, _filePicker.files[i]);
        }

        _filePicker.value = '';
    } else if (event.target.classList.contains('js-file-uploader-file-picker')) {
        const _filePicker = event.target;
        uploadFile(_filePicker.closest('.js-file-uploader'), _filePicker.files[0]);
        _filePicker.value = '';
    }
});

document.addEventListener('click', (event) => {
    if (event.target.classList.contains('js-uploaded-image-remove-button')) {
        removeImage(event.target.closest('.js-image-uploader'), event.target.closest('.js-uploaded-image'));
    }
});

document.querySelectorAll('.js-image-uploader[data-sortable]').forEach((_uploader) => {
    Sortable.create(_uploader, {
        draggable: '.js-uploaded-image',
        filter: '.js-uploaded-image-remove-button',
        onUpdate: () => {
            updateImagePositions(_uploader);
        },
    });
});

_form.addEventListener('submit', (event) => {
    if (_form.hasAttribute('data-uploads') && parseInt(_form.getAttribute('data-uploads')) > 0) {
        event.preventDefault();
    }
});
