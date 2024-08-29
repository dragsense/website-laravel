
import React from "react";


const ComplexLogics = ({ children, name, getStateValue, getPropValue, updateProp, updateState }) => {


    return (
        <div>
            {name}
            {children}
        </div>
    );

};


export default ComplexLogics;