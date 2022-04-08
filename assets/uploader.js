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
    let _image;

    if (_uploader.classList.contains('js-multiple-image-uploader')) {
        _image = _uploader.querySelector('template').content.firstElementChild.cloneNode(true);
        _image.innerHTML = _image.innerHTML.replaceAll('__name__', _uploader.getAttribute('data-index'));
        _uploader.appendChild(_image);

        updateImagePositions(_uploader);
        increaseDataAttribute(_uploader, 'index');
    } else {
        _image = _uploader.querySelector('.js-uploaded-image');
    }

    _image.classList.add('is-uploading');
    decreaseDataAttribute(_uploader, 'limit');
    increaseDataAttribute(_form, 'uploads');

    const formData = new FormData();
    formData.append('image', file, file.name);
    formData.append('min_height', _uploader.getAttribute('data-min-height'));
    formData.append('min_width', _uploader.getAttribute('data-min-width'));

    axios
        .post(lnorbyMediaImageUploadPath, formData)
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
    if (_uploader.classList.contains('js-multiple-image-uploader')) {
        _image.parentNode.removeChild(_image);
        updateImagePositions(_uploader);
    } else {
        _image.style.backgroundImage = '';
        _image.querySelector('.js-uploaded-image-media-id').value = '';
    }

    increaseDataAttribute(_uploader, 'limit');
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
        .post(lnorbyMediaFileUploadPath, formData)
        .then((response) => {
            _uploader.querySelector('.js-file-uploader-media-id').value = response.data.id;

            const _uploadedFile = _uploader.querySelector('.js-file-uploader-uploaded-file');
            _uploadedFile.setAttribute('href', response.data.url);
            _uploadedFile.setAttribute('download', response.data.name);
            _uploadedFile.innerText = response.data.name;

            _uploader.classList.add('is-uploaded');
        })
        .catch((error) => {
            alert(error.response.data);
        })
        .then(() => {
            _uploader.classList.remove('is-uploading');
            decreaseDataAttribute(_form, 'uploads');
        });
}

function removeFile(_uploader) {
    _uploader.querySelector('.js-file-uploader-media-id').value = '';

    const _uploadedFile = _uploader.querySelector('.js-file-uploader-uploaded-file');
    _uploadedFile.setAttribute('href', '');
    _uploadedFile.setAttribute('download', '');
    _uploadedFile.innerText = '';

    _uploader.classList.remove('is-uploaded');
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

    if (event.target.classList.contains('js-file-uploader-remove-button')) {
        removeFile(event.target.closest('.js-file-uploader'));
    }
});

document.querySelectorAll('.js-multiple-image-uploader[data-sortable]').forEach((_uploader) => {
    Sortable.create(_uploader, {
        draggable: '.js-uploaded-image',
        filter: '.js-uploaded-image-remove-button',
        onUpdate: () => {
            updateImagePositions(_uploader);
        },
    });
});

if (_form) {
    _form.addEventListener('submit', (event) => {
        if (_form.hasAttribute('data-uploads') && parseInt(_form.getAttribute('data-uploads')) > 0) {
            event.preventDefault();
        }
    });
}
