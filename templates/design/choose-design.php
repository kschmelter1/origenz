<div class="design-step design-step-choose-design container-fluid" v-if="step==2">

        <h2>choose a <u>design</u></h2>

        <div v-if="loading" class="design-loading">
            <div class="col-md-12 text-center">
                <div><i class="fa fa-spin fa-spinner"></i></div>
                <div class="loading-text">Creating Your Designs</div>
            </div>
        </div>
        <div v-else class="design-step-section row justify-content-center align-items-center">
            <div :class="{'col-md-4':true,'flag-image-preview':true,'active':shape==designImage.shape}" v-for="designImage in designImages" @click="setShape(designImage.shape)">
                <img :src="designImage.url" alt="Origenz Design" class="img-fluid" />
            </div>
        </div>

        <div class="design-step-section">
            <div v-if="errors.length>0" class="text-danger">
                <div v-for="error in errors">{{error}}</div>
            </div>
            <button class="btn btn-primary" type="button" @click="validateStep(2)">Continue to Step 3</button>
        </div>

</div>
