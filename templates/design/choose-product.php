<div class="design-step design-step-choose-product container-fluid" v-if="step==3">

        <h2>choose a <u>product</u></h2>

        <div class="design-step-section design-choice">
            <img :src="getDesignImageUrl(shape)" />
            <span>{{chosenDesign}}</span>
            <a href="#" @click.prevent="goToStep(1)">Re-design Graphic</a>
        </div>

        <div class="design-step-section product-category" v-for="section in appData.products">

                <h3>{{section.category.name}}</h3> <a :href="section.category.permalink" class="view-more">Browse all {{section.category.name}}</a>

                <div class="product-blurbs row">
                    <div class="col-md-3" v-for="product in section.products">
                      <div class="product-blurbs-single">
                        <img :src="product.image" class="img-fluid" :alt="product.name"/>
                        <div class="content">
                          <div class="product-title h5">{{product.name}}</div>
                          <a :href="product.permalink" class="btn btn-primary">Customize</a>
                        </div>
                      </div>
                        <!--<a :href="product.permalink">-->


                      <!--</a>-->
                    </div>
                </div>

        </div>

</div>
